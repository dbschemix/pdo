<?php

declare(strict_types=1);

namespace dbschemix\pdo;

use Closure;
use Override;
use PDO;
use PDOException;
use OutOfBoundsException;
use dbschemix\core\command\Command;
use dbschemix\core\command\CommandInterface;
use dbschemix\core\connection\ConnectionInterface;
use dbschemix\core\connection\DriverInterface;
use dbschemix\core\exception\ConfigurationException;
use dbschemix\core\exception\ConnectionException;
use dbschemix\core\Config;
use dbschemix\pdo\internal\Connection;
use dbschemix\pdo\internal\FactoryTransaction;
use dbschemix\pdo\internal\Transaction;
use dbschemix\pdo\internal\TransactionMysql;

use function dbschemix\core\internal\get_package_path;

/**
 * @api
 */
final class Driver implements DriverInterface
{
    /**
     * @var Closure():PDO
     */
    private readonly Closure $connectionFactory;

    private readonly Type $type;

    /**
     * @var non-empty-lowercase-string
     */
    private readonly string $dbname;

    private int $connectionTimer = 0;

    private ?Connection $connectionInstance = null;

    /**
     * @param non-empty-string $dsn
     * @param non-empty-string|null $username
     * @param non-empty-string|null $password
     * @throws ConfigurationException
     * @phpstan-ignore missingType.iterableValue
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
    ) {
        if (extension_loaded('PDO') === false) {
            throw new ConfigurationException('PDO: extension not loaded.');
        }

        [$this->type, $this->dbname] = prepareDSN($dsn);
        $this->connectionFactory = static fn(): PDO => new PDO($dsn, $username, $password, $options);
    }

    #[Override]
    public function getName(): string
    {
        return $this->type->value();
    }

    #[Override]
    public function getSourceName(): string
    {
        return $this->dbname;
    }

    /**
     * @throws OutOfBoundsException of package is not installed
     */
    #[Override]
    public function getSetupPath(): string
    {
        return get_package_path('dbschemix/core')
            . sprintf('/src/connection/%s/migration/', $this->type->value());
    }

    #[Override]
    public function makeCommand(Config $config): CommandInterface
    {
        return new Command($this->makeConnection(), $config);
    }

    /**
     * @infection-ignore-all
     * @throws ConnectionException
     */
    private function makeConnection(): ConnectionInterface
    {
        $timeout = 300;

        /**
         * @note если вдруг экземпляр класса Migrator будет использоваться как сервис, то переиспользуем коннект.
         * Но с ограничением по времени, долго держать в памяти не будем.
         */
        if (!$this->connectionInstance instanceof Connection || $this->connectionTimer < time()) {
            $this->connectionTimer = time() + $timeout;
            try {
                return $this->connectionInstance = new Connection(
                    ($this->connectionFactory)(),
                    $this->makeFactoryTransaction(),
                );
            } catch (PDOException $exception) {
                throw new ConnectionException($this, $exception);
            }
        }

        return $this->connectionInstance;
    }

    /**
     * Composition root: picks the transaction factory for the resolved dialect.
     *
     * @return class-string<FactoryTransaction>
     */
    private function makeFactoryTransaction(): string
    {
        if ($this->type === Type::PDO_MYSQL) {
            return TransactionMysql::class;
        }

        return Transaction::class;
    }
}

/**
 * @internal
 * @return array{0: Type, 1: non-empty-lowercase-string}
 * @throws ConfigurationException
 */
function prepareDSN(string $dsn): array
{
    $dsn = strtolower($dsn);

    $type = match (true) {
        str_starts_with($dsn, 'mysql:') => Type::PDO_MYSQL,
        str_starts_with($dsn, 'pgsql:') => Type::PDO_PGSQL,
        str_starts_with($dsn, 'sqlite:') => Type::PDO_SQLITE,
        default => throw new ConfigurationException('PDODriver: is not implemented.'),
    };

    if ($type === Type::PDO_SQLITE) {
        $partName = str_replace('sqlite:', '', $dsn);
        $dbName = str_starts_with($partName, ':')
            ? trim($partName, ':')
            : basename($partName, '.sqlite3');

        if ($dbName === '') {
            throw new ConfigurationException('PDODriver: dsn is incorrect.');
        }

        /**
         * @var non-empty-lowercase-string $dbName
         */
        return [$type, $dbName];
    } elseif (preg_match('~dbname=(?<dbname>[^;]+)~i', $dsn, $matches) > 0) {
        /**
         * @var array{"dbname": non-empty-lowercase-string} $matches
         * @phpstan-ignore varTag.nativeType
         */
        return [$type, $matches['dbname']];
    }

    throw new ConfigurationException('PDODriver: dsn is incorrect.');
}
