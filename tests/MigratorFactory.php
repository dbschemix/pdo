<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests;

use dbschemix\core\connection\DriverInterface;
use dbschemix\core\Migration;
use dbschemix\core\Migrator;
use dbschemix\core\MigratorInterface;

final readonly class MigratorFactory
{
    private function __construct()
    {
    }

    /**
     * @param non-empty-string $dbName
     */
    public static function makeFromDriver(DriverInterface $driver, string $dbName = 'memory'): MigratorInterface
    {
        return new Migrator(
            list: [
                new Migration(
                    path: __DIR__ . '/migration/sqlite/' . $dbName,
                    driver: $driver,
                ),
            ],
        );
    }
}
