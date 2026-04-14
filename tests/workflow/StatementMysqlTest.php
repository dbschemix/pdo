<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Override;
use Throwable;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use dbschemix\core\connection\TransactionInterface;
use dbschemix\pdo\internal\Connection;
use dbschemix\pdo\Type;

final class StatementMysqlTest extends TestCase
{
    private TransactionInterface $transaction;

    #[Override]
    protected function setUp(): void
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
    public function testExecActiveTransaction(): void
    {
        // default: false
        self::assertFalse($this->transaction->isActive());

        $this->transaction->exec(
            "INSERT INTO migration (name, version, atime) VALUES ('v1', 1, '2026-01-01 00:00:00')"
        );

        // statement INSERT: true
        self::assertTrue($this->transaction->isActive());

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
     * @return iterable<non-empty-string[]>
     */
    public static function additionSchemaQuery(): iterable
    {
        yield ['CREATE TABLE IF NOT EXISTS test (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)'];
        yield ['ALTER TABLE migration ADD COLUMN test TEXT DEFAULT NULL'];
        yield ['CREATE UNIQUE INDEX IF NOT EXISTS `UI_migration_name` ON migration (name)'];
        yield ['DROP INDEX IF EXISTS `UI_migration_name`'];
        yield ['DROP TABLE IF EXISTS test'];
    }

    /**
     * @param non-empty-string $query
     * @throws Throwable
     */
    #[DataProvider('additionSchemaQuery')]
    public function testExecNotActiveTransaction(string $query): void
    {
        // default: false
        self::assertFalse($this->transaction->isActive());

        $this->transaction->exec($query);

        // statement CREATE TABLE: false
        self::assertFalse($this->transaction->isActive());

        $state = $this->transaction->commit();
        self::assertTrue($state);
    }
}
