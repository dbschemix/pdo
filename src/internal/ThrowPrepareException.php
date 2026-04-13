<?php

declare(strict_types=1);

namespace dbschemix\pdo\internal;

use PDO;
use dbschemix\core\exception\PrepareException;

/**
 * @psalm-internal dbschemix\pdo
 */
trait ThrowPrepareException
{
    /**
     * @throws PrepareException
     */
    private function prepareException(PDO $connection): never
    {
        /**
         * @var array{0: string, 1: int, 2: string} $info PDO::errorInfo Spec
         */
        $info = $connection->errorInfo();
        throw new PrepareException(
            (string)new ErrorInfo($info)
        );
    }
}
