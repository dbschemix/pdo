<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Throwable;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use dbschemix\core\command\CommandInterface;
use dbschemix\core\exception\ActionException;
use dbschemix\core\Config;
use dbschemix\core\InputOptions;
use dbschemix\core\MigratorInterface;
use dbschemix\pdo\Driver;
use dbschemix\pdo\tests\MigratorFactory;

#[Test]
final class UpExactlyTest
{
    private MigratorInterface $migrator;

    private CommandInterface $command;

    #[BeforeTest]
    public function prepare(): void
    {
        $driver = new Driver(
            dsn: 'sqlite::memory:',
        );

        $this->migrator = MigratorFactory::makeFromDriver($driver, 'error');
        $this->command = $driver->makeCommand(new Config(table: 'migration'));
    }

    /**
     * @throws Throwable
     */
    public function upExactlyAll(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchApplied();
        Assert::blank($data);

        $this->migrator->up(new InputOptions(limit: 1));
        $data = $this->command->fetchApplied();
        Assert::count($data, 1);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        Assert::blank($data);

        try {
            $this->migrator->up();
        } catch (ActionException) {
        }

        // только первая миграция успешно
        $data = $this->command->fetchApplied();
        Assert::count($data, 1);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        Assert::blank($data);

        try {
            $this->migrator->up(new InputOptions(exactlyAll: true));
        } catch (ActionException) {
        }

        // всё или ничего
        $data = $this->command->fetchApplied();
        Assert::blank($data);
    }

    public function upExactlyAllException(): void
    {
        $this->migrator->init();

        Expect::exception(ActionException::class)
            ->withMessageContaining('SQLSTATE[HY000]: General error: 1 no such table:');

        $this->migrator->up(new InputOptions(exactlyAll: true));
    }

    /**
     * @throws Throwable
     */
    public function verify(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchApplied();
        Assert::blank($data);

        $this->migrator->up(new InputOptions(limit: 1));
        $data = $this->command->fetchApplied();
        Assert::count($data, 1);

        // точность версионирования
        usleep(10_000);

        try {
            $this->migrator->verify();
        } catch (ActionException) {
        }

        // только первая миграция успешно
        $data = $this->command->fetchApplied();
        Assert::count($data, 1);
    }

    public function verifyException(): void
    {
        $this->migrator->init();

        Expect::exception(ActionException::class)
            ->withMessageContaining('SQLSTATE[HY000]: General error: 1 no such table:');

        $this->migrator->verify();
    }
}
