<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests;

use Testo\Assert;
use Testo\Expect;
use Testo\Test;
use dbschemix\core\exception\ConfigurationException;
use dbschemix\pdo\Driver;

#[Test]
final class DriverTest
{
    public function typeDriver(): void
    {
        $driver = new Driver(
            dsn: 'pgsql:host=postgres;port=5432;dbname=main',
        );

        Assert::same($driver->getName(), 'pgsql');
        Assert::same($driver->getSourceName(), 'main');

        $driver = new Driver(
            dsn: 'MYSQL:host=mysql;dbname=copyDb',
        );

        Assert::same($driver->getName(), 'mysql');
        Assert::same($driver->getSourceName(), 'copydb');

        $driver = new Driver(
            dsn: 'sqlite::memory:',
        );

        Assert::same($driver->getName(), 'sqlite');
        Assert::same($driver->getSourceName(), 'memory');

        $driver = new Driver(
            dsn: 'sqlite:tests/data/sqlite/db.sqlite3',
        );

        Assert::same($driver->getName(), 'sqlite');
        Assert::same($driver->getSourceName(), 'db');
    }

    public function configurationException(): void
    {
        Expect::exception(ConfigurationException::class);

        new Driver(
            dsn: 'unknown:',
        );
    }

    public function dsnConfigurationException(): void
    {
        Expect::exception(ConfigurationException::class)
            ->withMessage('PDODriver: dsn is incorrect.');

        new Driver(
            dsn: 'mysql::memory:',
        );
    }

    public function dsnEmptyConfigurationException(): void
    {
        Expect::exception(ConfigurationException::class)
            ->withMessage('PDODriver: dsn is incorrect.');

        new Driver(
            dsn: 'sqlite:',
        );
    }
}
