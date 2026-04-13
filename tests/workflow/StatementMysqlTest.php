<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Override;
use Throwable;
use PDO;
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
     * @throws Throwable
     */
    public function testExecNotActiveTransaction(): void
    {
        // default: false
        self::assertFalse($this->transaction->isActive());

        $this->transaction->exec(
            "CREATE TABLE test (name TEXT PRIMARY KEY, version INTEGER DEFAULT 0, atime TEXT)"
        );

        // statement CREATE TABLE: false
        self::assertFalse($this->transaction->isActive());

        $state = $this->transaction->commit();
        self::assertTrue($state);
    }
}
