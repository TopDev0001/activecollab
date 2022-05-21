<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(
        Option::PATHS,
        [
            __DIR__ . '/activecollab/current',
            __DIR__ . '/tests/acceptance',
            __DIR__ . '/tests/phpunit',
        ]
    );

    $parameters->set(
        Option::SKIP,
        [
            __DIR__ . '/activecollab/current/vendor'
        ]
    );

    $parameters->set(
        Option::BOOTSTRAP_FILES,
        [
            __DIR__ . '/config/config.php',
            __DIR__ . '/activecollab/current/vendor/autoload.php',
        ]
    );

    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_74);

    // Define what rule sets will be applied
    // $containerConfigurator->import(SetList::DEAD_CODE);

    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
    $services->set(UnusedForeachValueToArrayKeysRector::class);
};
