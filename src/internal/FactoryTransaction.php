<?php

declare(strict_types=1);

namespace dbschemix\pdo\internal;

use PDO;
use dbschemix\core\connection\TransactionInterface;

/**
 * @psalm-internal dbschemix\pdo
 */
interface FactoryTransaction
{
    public static function begin(PDO $connection): TransactionInterface;
}
