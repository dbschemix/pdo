<?php

declare(strict_types=1);

namespace dbschemix\pdo\internal;

use Override;
use PDO;
use dbschemix\core\connection\ConnectionInterface;
use dbschemix\core\connection\TransactionInterface;
use dbschemix\pdo\Type;

/**
 * @psalm-internal dbschemix\pdo
 */
final readonly class Connection implements ConnectionInterface
{
    use ThrowPrepareException;

    public function __construct(
        private PDO $connection,
        private Type $driverType,
    ) {
    }

    #[Override]
    public function beginTransaction(): TransactionInterface
    {
        $class = $this->driverType->makeFactoryTransaction();
        return $class::begin($this->connection);
    }

    #[Override]
    public function fetchRecord(string $query, array $params = []): array
    {
        $statement = $this->connection->prepare($query);
        if ($statement === false) {
            $this->prepareException($this->connection);
        }

        if ($statement->execute($params)) {
            /**
             * @var array<non-empty-string, non-negative-int>
             */
            return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        return [];
    }

    #[Override]
    public function exec(string $query, array $params = []): void
    {
        if ($params === []) {
            $this->connection->exec($query);
            return;
        }

        $statement = $this->connection->prepare($query);
        if ($statement === false) {
            $this->prepareException($this->connection);
        }

        $statement->execute($params);
    }
}
