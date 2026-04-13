<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Override;
use Throwable;
use PDO;
use PHPUnit\Framework\TestCase;
use dbschemix\core\connection\TransactionInterface;
use dbschemix\pdo\internal\Transaction;

final class StatementTest extends TestCase
{
    private PDO $pdo;

    private TransactionInterface $transaction;

    #[Override]
    protected function setUp(): void
    {
        $this->pdo = new PDO(dsn: 'sqlite::memory:');
        $this->pdo->exec(
            'CREATE TABLE migration (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)'
        );

        $this->transaction = Transaction::begin($this->pdo);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->transaction->rollback();
    }

    /**
     * @throws Throwable
     */
    public function testExecWithoutParams(): void
    {
        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        self::assertCount(1, $data);
        self::assertArrayHasKey('v1', $data);
    }

    /**
     * @throws Throwable
     */
    public function testExecCommitTransaction(): void
    {
        self::assertTrue($this->transaction->isActive());

        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        $state = $this->transaction->commit();
        self::assertTrue($state);
        self::assertFalse($this->transaction->isActive());

        // not active
        $state = $this->transaction->commit();
        self::assertTrue($state);

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        self::assertCount(1, $data);
    }

    /**
     * @throws Throwable
     */
    public function testExecRollbackTransaction(): void
    {
        self::assertTrue($this->transaction->isActive());

        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        $state = $this->transaction->rollback();
        self::assertTrue($state);
        self::assertFalse($this->transaction->isActive());

        // not active
        $state = $this->transaction->rollback();
        self::assertTrue($state);

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        self::assertEmpty($data);
    }

    /**
     * @throws Throwable
     */
    public function testExecWithNamedParams(): void
    {
        $this->transaction->exec(
            'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
            ['name' => '202501010000_create_users', 'version' => 42, 'atime' => '2026-01-01 00:00:00'],
        );

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        self::assertCount(1, $data);
        self::assertArrayHasKey('202501010000_create_users', $data);
        self::assertEquals(42, $data['202501010000_create_users']);
    }

    /**
     * Параметр со спецсимволами SQL должен быть записан как литерал, а не интерпретирован как SQL.
     *
     * @throws Throwable
     */
    public function testExecParamsAreNotInterpolated(): void
    {
        $maliciousName = "'); DROP TABLE migration; --";

        $this->transaction->exec(
            'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
            ['name' => $maliciousName, 'version' => 1, 'atime' => '2026-01-01 00:00:00'],
        );

        $data = $this->transaction->fetchRecord('SELECT name, version FROM migration');
        self::assertCount(1, $data);
        self::assertArrayHasKey($maliciousName, $data);
    }

    /**
     * @throws Throwable
     */
    public function testExecMultipleRowsWithParams(): void
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
        self::assertCount(3, $data);
        self::assertEquals(1, $data['202501010000_first']);
        self::assertEquals(2, $data['202501010001_second']);
        self::assertEquals(3, $data['202501010002_third']);
    }

    /**
     * @throws Throwable
     */
    public function testFetchRecordWithParams(): void
    {
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v1', 10, '2026-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v2', 20, '2026-01-02 00:00:00')");
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v3', 10, '2026-01-03 00:00:00')");

        $data = $this->transaction->fetchRecord(
            'SELECT name, version FROM migration WHERE version = :version',
            ['version' => 10],
        );

        self::assertCount(2, $data);
        self::assertArrayHasKey('v1', $data);
        self::assertArrayHasKey('v3', $data);
        self::assertArrayNotHasKey('v2', $data);
    }

    /**
     * @throws Throwable
     */
    public function testFetchRecordWithParamsNoMatch(): void
    {
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')");

        $data = $this->transaction->fetchRecord(
            'SELECT name, version FROM migration WHERE version = :version',
            ['version' => 999],
        );

        self::assertEmpty($data);
    }
}
