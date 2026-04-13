<?php

declare(strict_types=1);

namespace dbschemix\pdo;

use dbschemix\pdo\internal\FactoryTransaction;
use dbschemix\pdo\internal\Transaction;
use dbschemix\pdo\internal\TransactionMysql;

/**
 * @api
 */
enum Type
{
    case PDO_PGSQL;

    case PDO_MYSQL;

    case PDO_SQLITE;

    /**
     * @return non-empty-lowercase-string
     */
    public function value(): string
    {
        [, $db] = explode('_', $this->name);

        /**
         * @var non-empty-lowercase-string
         */
        return strtolower($db);
    }

    /**
     * @return class-string<FactoryTransaction>
     */
    public function makeFactoryTransaction(): string
    {
        if ($this === self::PDO_MYSQL) {
            return TransactionMysql::class;
        }

        return Transaction::class;
    }
}
