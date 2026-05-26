<?php

declare(strict_types=1);

namespace dbschemix\pdo;

/**
 * @api
 */
enum Type
{
    case PDO_PGSQL;

    case PDO_MYSQL;

    case PDO_MSSQL;

    case PDO_SQLITE;

    /**
     * @return non-empty-lowercase-string
     */
    public function value(): string
    {
        /**
         * @var non-empty-lowercase-string
         */
        return strtolower(substr($this->name, 4));
    }
}
