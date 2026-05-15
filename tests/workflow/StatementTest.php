<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Throwable;
use PDO;
use Testo\Assert;
use Testo\Lifecycle\AfterTest;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use dbschemix\core\connection\TransactionInterface;
use dbschemix\pdo\internal\Transaction;

#[Test]
final class StatementTest
{
    private PDO $pdo;

    private TransactionInterface $transaction;

    #[BeforeTest]
    public function init(): void
    {
        $this->pdo = new PDO(dsn: 'sqlite::memory:');
        $this->pdo->exec(
            'CREATE TABLE migration (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)'
        );

        $this->transaction = Transaction::begin($this->pdo);
    }

    #[AfterTest]
    public function clean(): void
    {
        $this->transaction->rollback();
    }

    /**
     * @throws Throwable
     */
    public function execWithoutParams(): void
    {
        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 1);
        Assert::array($data)->hasKeys('v1');
    }

    /**
     * @throws Throwable
     */
    public function execCommitTransaction(): void
    {
        Assert::true($this->transaction->isActive());

        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        $state = $this->transaction->commit();
        Assert::true($state);
        Assert::false($this->transaction->isActive());

        // not active
        $state = $this->transaction->commit();
        Assert::true($state);

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 1);
    }

    /**
     * @throws Throwable
     */
    public function execRollbackTransaction(): void
    {
        Assert::true($this->transaction->isActive());

        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        $state = $this->transaction->rollback();
        Assert::true($state);
        Assert::false($this->transaction->isActive());

        // not active
        $state = $this->transaction->rollback();
        Assert::true($state);

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        Assert::blank($data);
    }

    /**
     * @throws Throwable
     */
    public function execWithNamedParams(): void
    {
        $this->transaction->exec(
            'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
            ['name' => '202501010000_create_users', 'version' => 42, 'atime' => '2026-01-01 00:00:00'],
        );

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 1);
        Assert::array($data)->hasKeys('202501010000_create_users');
        Assert::equals($data['202501010000_create_users'], 42);
    }

    /**
     * Параметр со спецсимволами SQL должен быть записан как литерал, а не интерпретирован как SQL.
     *
     * @throws Throwable
     */
    public function execParamsAreNotInterpolated(): void
    {
        $maliciousName = "'); DROP TABLE migration; --";

        $this->transaction->exec(
            'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
            ['name' => $maliciousName, 'version' => 1, 'atime' => '2026-01-01 00:00:00'],
        );

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 1);
        Assert::array($data)->hasKeys($maliciousName);
    }

    /**
     * @throws Throwable
     */
    public function execMultipleRowsWithParams(): void
    {
        $rows = [
            ['name' => '202501010000_first', 'version' => 1, 'atime' => '2026-01-01 00:00:00'],
            ['name' => '202501010001_second', 'version' => 2, 'atime' => '2026-01-02 00:00:00'],
            ['name' => '202501010002_third', 'version' => 3, 'atime' => '2026-01-03 00:00:00'],
        ];

        foreach ($rows as $row) {
            $this->transaction->exec(
                'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
                $row,
            );
        }

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 3);
        Assert::equals($data['202501010000_first'], 1);
        Assert::equals($data['202501010001_second'], 2);
        Assert::equals($data['202501010002_third'], 3);
    }

    /**
     * @throws Throwable
     */
    public function fetchRecordWithParams(): void
    {
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v1', 10, '2026-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v2', 20, '2026-01-02 00:00:00')");
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v3', 10, '2026-01-03 00:00:00')");

        $data = $this->transaction->fetchRecord(
            'SELECT name, version FROM migration WHERE version = :version',
            ['version' => 10],
        );

        Assert::count($data, 2);
        Assert::array($data)->hasKeys('v1', 'v3');
        Assert::array($data)->doesNotHaveKeys('v2');
    }

    /**
     * @throws Throwable
     */
    public function fetchRecordWithParamsNoMatch(): void
    {
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')");

        $data = $this->transaction->fetchRecord(
            'SELECT name, version FROM migration WHERE version = :version',
            ['version' => 999],
        );

        Assert::blank($data);
    }
}
