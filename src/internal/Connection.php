<?php

declare(strict_types=1);

namespace dbschemix\pdo\internal;

use Override;
use PDO;
use dbschemix\core\connection\ConnectionInterface;
use dbschemix\core\connection\TransactionInterface;

/**
 * @psalm-internal dbschemix\pdo
 */
final readonly class Connection implements ConnectionInterface
{
    use ThrowPrepareException;

    /**
     * @param class-string<FactoryTransaction> $factoryTransaction
     */
    public function __construct(
        private PDO $connection,
        private string $factoryTransaction,
    ) {
    }

    #[Override]
    public function beginTransaction(): TransactionInterface
    {
        return ($this->factoryTransaction)::begin($this->connection);
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
