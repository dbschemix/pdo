<?php

declare(strict_types=1);

namespace dbschemix\pdo\internal;

use Override;
use PDO;
use dbschemix\core\connection\TransactionInterface;

/**
 * @psalm-internal dbschemix\pdo
 */
final class Transaction extends Statement implements TransactionInterface, FactoryTransaction
{
    #[Override]
    public static function begin(PDO $connection): TransactionInterface
    {
        return new self($connection, $connection->beginTransaction());
    }

    /**
     * @infection-ignore-all
     */
    #[Override]
    public function isActive(): bool
    {
        $this->transactionActive = $this->transactionActive || $this->connection->inTransaction();

        return $this->transactionActive;
    }

    #[Override]
    public function commit(): bool
    {
        if ($this->isActive()) {
            $this->transactionActive = false;
            return $this->connection->commit();
        }

        return true;
    }

    #[Override]
    public function rollback(): bool
    {
        if ($this->isActive()) {
            $this->transactionActive = false;
            return $this->connection->rollBack();
        }

        return true;
    }
}
