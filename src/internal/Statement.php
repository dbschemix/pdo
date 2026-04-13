<?php

declare(strict_types=1);

namespace dbschemix\pdo\internal;

use PDO;
use dbschemix\core\connection\StatementInterface;

/**
 * @psalm-internal dbschemix\pdo
 */
abstract class Statement implements StatementInterface
{
    use ThrowPrepareException;

    protected function __construct(
        protected readonly PDO $connection,
        protected bool $transactionActive,
    ) {
    }

    #[\Override]
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

    #[\Override]
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
