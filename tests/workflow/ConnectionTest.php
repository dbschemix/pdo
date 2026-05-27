<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

// Note: the `return []` branch in fetchRecord() (when execute() returns false) and its
// `prepareException()` branch (when prepare() returns false) are not reachable through an
// in-memory SQLite PDO handle under the default ERRMODE_EXCEPTION, and are intentionally
// left without direct coverage here.

use Throwable;
use PDO;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use dbschemix\core\exception\PrepareException;
use dbschemix\pdo\internal\Connection;
use dbschemix\pdo\internal\Transaction;

#[Test]
final class ConnectionTest
{
    private PDO $pdo;

    private Connection $connection;

    #[BeforeTest]
    public function init(): void
    {
        $this->pdo = new PDO(dsn: 'sqlite::memory:');
        $this->pdo->exec(
            'CREATE TABLE migration (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)'
        );

        $this->connection = new Connection($this->pdo, Transaction::class);
    }

    /**
     * exec() without params uses PDO::exec() directly (the empty-params branch).
     * Verified by reading the inserted row back through fetchRecord().
     *
     * @throws Throwable
     */
    public function execWithoutParams(): void
    {
        $this->connection->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        $data = $this->connection->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 1);
        Assert::array($data)->hasKeys('v1');
    }

    /**
     * exec() with named params uses prepare()+execute() (the params branch).
     * The param values are bound as literals, not interpolated into SQL.
     *
     * @throws Throwable
     */
    public function execWithNamedParams(): void
    {
        $this->connection->exec(
            'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
            ['name' => '202501010000_create_users', 'version' => 42, 'atime' => '2026-01-01 00:00:00'],
        );

        $data = $this->connection->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 1);
        Assert::array($data)->hasKeys('202501010000_create_users');
        Assert::equals($data['202501010000_create_users'], 42);
    }

    /**
     * exec() with params treats SQL-special characters in parameter values as literals,
     * not as SQL fragments — the table must survive and contain exactly one row.
     *
     * @throws Throwable
     */
    public function execParamsAreNotInterpolated(): void
    {
        $maliciousName = "'); DROP TABLE migration; --";

        $this->connection->exec(
            'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
            ['name' => $maliciousName, 'version' => 1, 'atime' => '2026-01-01 00:00:00'],
        );

        $data = $this->connection->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 1);
        Assert::array($data)->hasKeys($maliciousName);
    }

    /**
     * fetchRecord() without params returns all rows as name→version map (FETCH_KEY_PAIR).
     *
     * @throws Throwable
     */
    public function fetchRecordWithoutParams(): void
    {
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v1', 10, '2026-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v2', 20, '2026-01-02 00:00:00')");

        $data = $this->connection->fetchRecord('SELECT name, version FROM migration');

        Assert::count($data, 2);
        Assert::array($data)->hasKeys('v1', 'v2');
        Assert::equals($data['v1'], 10);
        Assert::equals($data['v2'], 20);
    }

    /**
     * fetchRecord() with params filters rows by the bound value.
     *
     * @throws Throwable
     */
    public function fetchRecordWithParams(): void
    {
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v1', 10, '2026-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v2', 20, '2026-01-02 00:00:00')");
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v3', 10, '2026-01-03 00:00:00')");

        $data = $this->connection->fetchRecord(
            'SELECT name, version FROM migration WHERE version = :version',
            ['version' => 10],
        );

        Assert::count($data, 2);
        Assert::array($data)->hasKeys('v1', 'v3');
        Assert::array($data)->doesNotHaveKeys('v2');
    }

    /**
     * fetchRecord() returns an empty array when the query matches no rows.
     *
     * @throws Throwable
     */
    public function fetchRecordReturnsEmptyArrayOnNoMatch(): void
    {
        $this->pdo->exec("INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')");

        $data = $this->connection->fetchRecord(
            'SELECT name, version FROM migration WHERE version = :version',
            ['version' => 999],
        );

        Assert::blank($data);
    }

    /**
     * exec() without params raises PrepareException when the underlying PDO::exec()
     * returns false. Forces the failure by switching the handle to ERRMODE_SILENT and
     * passing syntactically invalid SQL.
     *
     * @throws Throwable
     */
    public function execRaisesPrepareExceptionOnFailure(): void
    {
        Expect::exception(PrepareException::class);

        $pdo = new PDO(dsn: 'sqlite::memory:', options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        ]);
        $connection = new Connection($pdo, Transaction::class);

        $connection->exec('THIS IS NOT VALID SQL');
    }

    /**
     * Three consecutive exec()-with-params calls each use a fresh prepare()+execute() cycle.
     * fetchRecord() must return all three rows, proving no statement handle is reused
     * incorrectly between calls.
     *
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
            $this->connection->exec(
                'INSERT INTO migration (name, version, atime) VALUES (:name, :version, :atime)',
                $row,
            );
        }

        $data = $this->connection->fetchRecord('SELECT name, version FROM migration');
        Assert::count($data, 3);
        Assert::equals($data['202501010000_first'], 1);
        Assert::equals($data['202501010001_second'], 2);
        Assert::equals($data['202501010002_third'], 3);
    }
}
