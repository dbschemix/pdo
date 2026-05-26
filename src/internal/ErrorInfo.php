<?php

declare(strict_types=1);

namespace dbschemix\pdo\internal;

use Override;
use Stringable;

/**
 * @psalm-internal dbschemix\pdo
 * @see https://www.php.net/manual/en/pdo.errorinfo.php
 */
final readonly class ErrorInfo implements Stringable
{
    /**
     * @var non-empty-string
     */
    private string $message;

    /**
     * @param array{0: string, 1: int, 2: string} $errorInfo
     */
    public function __construct(array $errorInfo)
    {
        $this->message = sprintf('SQLSTATE[%s]: error: %s', $errorInfo[0], $errorInfo[2]);
    }

    #[Override]
    public function __toString(): string
    {
        return $this->message;
    }
}
