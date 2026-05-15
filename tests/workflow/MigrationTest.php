<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Throwable;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use dbschemix\core\command\CommandInterface;
use dbschemix\core\Config;
use dbschemix\core\InputOptions;
use dbschemix\core\MigratorInterface;
use dbschemix\pdo\tests\MigratorFactory;
use dbschemix\pdo\Driver;

/**
 * Верхнеуровневая работа приложения.
 */
#[Test]
final class MigrationTest
{
    private MigratorInterface $migrator;

    private CommandInterface $command;

    #[BeforeTest]
    public function prepare(): void
    {
        $driver = new Driver(
            dsn: 'sqlite::memory:',
        );

        $this->migrator = MigratorFactory::makeFromDriver($driver);
        $this->command = $driver->makeCommand(new Config(table: 'migration'));
    }

    /**
     * @throws Throwable
     */
    public function init(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchApplied();
        Assert::blank($data);
    }

    /**
     * @throws Throwable
     */
    public function up(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        $countMigration = count($data);
        Assert::int($countMigration)->greaterThanOrEqual(3);

        // sort order
        $names = array_keys($data);
        Assert::same($names[0], '202501021025_account_email.sql');
        Assert::same($names[1], '202501021024_account_create.sql');
        Assert::same($names[2], '202501011024_entity_create.sql');

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        Assert::count($data, $countMigration);
    }

    /**
     * @throws Throwable
     */
    public function down(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        Assert::int(count($data))->greaterThanOrEqual(3);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        Assert::blank($data);
    }

    /**
     * @throws Throwable
     */
    public function redo(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        Assert::int(count($data))->greaterThanOrEqual(3);

        $version = (int)current($data);
        Assert::int($version)->greaterThan(0);

        usleep(10_000);

        $this->migrator->redo();
        $data = $this->command->fetchApplied();
        Assert::int(count($data))->greaterThanOrEqual(3);

        $versionNew = (int)current($data);
        Assert::int($versionNew)->greaterThan(0);

        // новая версия больше старой
        Assert::int($versionNew)->greaterThan($version);
    }

    /**
     * @throws Throwable
     */
    public function verify(): void
    {
        $this->migrator->init();

        $this->migrator->up(new InputOptions(limit: 1));
        $data = $this->command->fetchApplied();
        Assert::count($data, 1);

        $version = (int)current($data);
        Assert::int($version)->greaterThan(0);

        usleep(10_000);

        $this->migrator->verify();

        $data = $this->command->fetchApplied();
        Assert::count($data, 1);

        $versionNew = (int)current($data);
        Assert::equals($versionNew, $version);
    }

    /**
     * @throws Throwable
     */
    public function fixture(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        $countMigration = count($data);
        Assert::int($countMigration)->greaterThan(0);

        // applied fixture no saved
        $this->migrator->fixture();
        $data = $this->command->fetchApplied();
        Assert::count($data, $countMigration);
    }
}
