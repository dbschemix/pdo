<?php

declare(strict_types=1);

use dbschemix\core\Migration;
use dbschemix\pdo\Driver;
use dbschemix\core\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';

$migrator = new Migrator(
    list: [
        new Migration(
            path: __DIR__ . '/migration/postgres/main',
            driver: new Driver(
                dsn: 'pgsql:host=postgres;port=5432;dbname=main',
                username: 'postgres',
                password: 'postgres',
            )
        ),
        // database copy, single data source migrations
        new Migration(
            path: __DIR__ . '/migration/postgres/main',
            driver: new Driver(
                dsn: 'pgsql:host=postgres;port=5432;dbname=maincopy',
                username: 'postgres',
                password: 'postgres',
            )
        ),
    ],
);

try {
    $migrator->init();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

try {
    $migrator->up();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

try {
    $migrator->up();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

try {
    $migrator->down();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}
