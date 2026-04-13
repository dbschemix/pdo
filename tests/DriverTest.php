<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests;

use PHPUnit\Framework\TestCase;
use dbschemix\core\exception\ConfigurationException;
use dbschemix\pdo\Driver;

final class DriverTest extends TestCase
{
    public function testTypeDriver(): void
    {
        $driver = new Driver(
            dsn: 'pgsql:host=postgres;port=5432;dbname=main',
        );

        self::assertEquals('pgsql', $driver->getName());
        self::assertEquals('main', $driver->getSourceName());

        $driver = new Driver(
            dsn: 'MYSQL:host=mysql;dbname=copyDb',
        );

        self::assertEquals('mysql', $driver->getName());
        self::assertEquals('copydb', $driver->getSourceName());

        $driver = new Driver(
            dsn: 'sqlite::memory:',
        );

        self::assertEquals('sqlite', $driver->getName());
        self::assertEquals('memory', $driver->getSourceName());

        $driver = new Driver(
            dsn: 'sqlite:tests/data/sqlite/db.sqlite3',
        );

        self::assertEquals('sqlite', $driver->getName());
        self::assertEquals('db', $driver->getSourceName());
    }

    public function testConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);

        new Driver(
            dsn: 'unknown:',
        );
    }

    public function testDSNConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('PDODriver: dsn is incorrect.');

        new Driver(
            dsn: 'mysql::memory:',
        );
    }

    public function testDSNEmptyConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('PDODriver: dsn is incorrect.');

        new Driver(
            dsn: 'sqlite:',
        );
    }
}
