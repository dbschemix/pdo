<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;
use Testo\Codecov\CodecovPlugin;
use Testo\Codecov\Report\PhpUnitXmlReport;

return new ApplicationConfig(
    suites: [
        new SuiteConfig(
            name: 'integration',
            location: ['tests'],
        ),
    ],
    plugins: [
        new CodecovPlugin(
            reports: [
                new PhpUnitXmlReport(__DIR__ . '/runtime/coverage/coverage-xml'),
            ],
        ),
    ],
);
