<?php

declare(strict_types=1);

namespace dbschemix\pdo\tests\workflow;

use Override;
use Throwable;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use dbschemix\pdo\Driver;
use dbschemix\pdo\tests\MigratorFactory;
use dbschemix\core\command\CommandInterface;
use dbschemix\core\Config;
use dbschemix\core\InputOptions;
use dbschemix\core\MigratorInterface;

/**
 * Верхнеуровневая работа приложения.
 */
final class MigrationTest extends TestCase
{
    private MigratorInterface $migrator;

    private CommandInterface $command;

    #[Override]
    protected function setUp(): void
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
    public function testInit(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);
    }

    /**
     * @throws Throwable
     */
    #[Depends('testInit')]
    public function testUp(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        $countMigration = count($data);
        self::assertGreaterThanOrEqual(3, $countMigration);

        // sort order
        $names = array_keys($data);
        self::assertEquals('202501021025_account_email.sql', $names[0]);
        self::assertEquals('202501021024_account_create.sql', $names[1]);
        self::assertEquals('202501011024_entity_create.sql', $names[2]);

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        self::assertCount($countMigration, $data);
    }

    /**
     * @throws Throwable
     */
    #[Depends('testInit')]
    public function testDown(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        self::assertGreaterThanOrEqual(3, $data);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);
    }

    /**
     * @throws Throwable
     */
    #[Depends('testInit')]
    public function testRedo(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        self::assertGreaterThanOrEqual(3, $data);

        $version = (int)current($data);
        self::assertGreaterThan(0, $version);

        usleep(10_000);

        $this->migrator->redo();
        $data = $this->command->fetchApplied();
        self::assertGreaterThanOrEqual(3, $data);

        $versionNew = (int)current($data);
        self::assertGreaterThan(0, $versionNew);

        // новая версия больше старой
        self::assertGreaterThan($version, $versionNew);
    }

    /**
     * @throws Throwable
     */
    #[Depends('testInit')]
    public function testVerify(): void
    {
        $this->migrator->init();

        $this->migrator->up(new InputOptions(limit: 1));
        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);

        $version = (int)current($data);
        self::assertGreaterThan(0, $version);

        usleep(10_000);

        $this->migrator->verify();

        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);

        $versionNew = (int)current($data);
        self::assertEquals($version, $versionNew);
    }

    /**
     * @throws Throwable
     */
    #[Depends('testInit')]
    public function testFixture(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        $countMigration = count($data);
        self::assertGreaterThan(0, $countMigration);

        // applied fixture no saved
        $this->migrator->fixture();
        $data = $this->command->fetchApplied();
        self::assertCount($countMigration, $data);
    }
}
