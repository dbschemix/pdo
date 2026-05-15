<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Throwable;
use PDO;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use dbschemix\core\connection\TransactionInterface;
use dbschemix\pdo\internal\Connection;
use dbschemix\pdo\Type;

#[Test]
final class StatementMysqlTest
{
    private TransactionInterface $transaction;

    #[BeforeTest]
    public function init(): void
    {
        $pdo = new PDO(dsn: 'sqlite::memory:');
        $pdo->exec(
            'CREATE TABLE migration (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)'
        );

        $this->transaction = (new Connection($pdo, Type::PDO_MYSQL))->beginTransaction();
    }

    /**
     * @throws Throwable
     */
    public function execActiveTransaction(): void
    {
        // default: false
        Assert::false($this->transaction->isActive());

        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        // statement INSERT: true
        Assert::true($this->transaction->isActive());

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
     * Non-parameterized DDL case.
     *
     * Testo's coverage bridge does not attribute per-line coverage to
     * #[DataProvider] methods, so {@see execNotActiveTransaction} is invisible
     * to Infection and the DDL detection in tryBeginTransaction() would stay
     * uncovered. This plain test pins the "DDL does not open a transaction"
     * contract so the preg_match guard is mutation-covered.
     *
     * @throws Throwable
     */
    public function execSchemaQueryNotActiveTransaction(): void
    {
        // default: false
        Assert::false($this->transaction->isActive());

        $this->transaction->exec(
            'CREATE TABLE IF NOT EXISTS test (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)'
        );

        // statement CREATE TABLE: still not active, DDL must not open a transaction
        Assert::false($this->transaction->isActive());

        $state = $this->transaction->commit();
        Assert::true($state);
    }

    /**
     * @return iterable<non-empty-string, non-empty-string[]>
     */
    public static function additionSchemaQuery(): iterable
    {
        yield 'create table' => [
            'CREATE TABLE IF NOT EXISTS test (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)',
        ];
        yield 'alter table' => ['ALTER TABLE migration ADD COLUMN test TEXT DEFAULT NULL'];
        yield 'create index' => ['CREATE UNIQUE INDEX IF NOT EXISTS `UI_migration_name` ON migration (name)'];
        yield 'drop index' => ['DROP INDEX IF EXISTS `UI_migration_name`'];
        yield 'drop table' => ['DROP TABLE IF EXISTS test'];
    }

    /**
     * @param non-empty-string $query
     * @throws Throwable
     */
    #[DataProvider('additionSchemaQuery')]
    public function execNotActiveTransaction(string $query): void
    {
        // default: false
        Assert::false($this->transaction->isActive());

        $this->transaction->exec($query);

        // statement CREATE TABLE: false
        Assert::false($this->transaction->isActive());

        $state = $this->transaction->commit();
        Assert::true($state);
    }
}
