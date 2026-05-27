<?php

declare(strict_types=1);

use DI\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use dbschemix\pdo\Driver;
use dbschemix\pdo\example\presentation\DownCommand;
use dbschemix\pdo\example\presentation\CreateCommand;
use dbschemix\pdo\example\presentation\FixtureCommand;
use dbschemix\pdo\example\presentation\VerifyCommand;
use dbschemix\pdo\example\presentation\InitCommand;
use dbschemix\pdo\example\presentation\RedoCommand;
use dbschemix\pdo\example\presentation\UpCommand;
use dbschemix\core\Migration;
use dbschemix\core\Migrator;
use dbschemix\core\MigratorInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new Container(
    [
        MigratorInterface::class => new Migrator(
            list: [
                new Migration(
                    path: __DIR__ . '/migration/sqlite/memory',
                    driver: new Driver(
                        dsn: 'sqlite:' . __DIR__ . '/data/sqlite/db.sqlite3',
                    )
                ),
            ],
        ),
    ]
);

$console = new Application();
$console->setCommandLoader(
    new ContainerCommandLoader(
        $container,
        [
            'migrate:init' => InitCommand::class,
            'migrate:up' => UpCommand::class,
            'migrate:down' => DownCommand::class,
            'migrate:redo' => RedoCommand::class,
            'migrate:verify' => VerifyCommand::class,
            'migrate:fixture' => FixtureCommand::class,
            'migrate:create' => CreateCommand::class,
        ],
    )
);

try {
    exit($console->run());
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(Command::FAILURE);
}
