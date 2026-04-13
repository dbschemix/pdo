<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;

return Rector\Config\RectorConfig::configure()
    ->withPaths(
        [
            __DIR__ . '/src',
            __DIR__ . '/tests',
            __DIR__ . '/example',
        ]
    )
    ->withParallel()
    ->withCache('/tmp/var/rector')
    ->withPhpSets()
    ->withRules(
        [
            InlineConstructorDefaultToPropertyRector::class,
        ]
    );
