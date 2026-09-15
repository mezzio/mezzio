<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->layer('Core', [
        'src/Application.php',
        'src/MiddlewareContainer.php',
        'src/MiddlewareFactory.php',
        'src/MiddlewareFactoryInterface.php',
        'src/constants.php',
        'src/constants.legacy.php',
    ])
    ->layer('Config', 'src/ConfigProvider.php')
    ->layer('Exception', 'src/Exception')
    ->layer('Response', 'src/Response')
    ->layer('Handler', 'src/Handler')
    ->layer('Middleware', 'src/Middleware')
    ->layer('Router', 'src/Router')
    ->layerPattern('Router', '/^Mezzio\\\\Router\\\\.*$/')
    ->layer('Container', 'src/Container')
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->ruleset([
        'Config'     => ['Container', '+Core', '+Handler'],
        'Exception'  => [],
        'Response'   => [],
        'Handler'    => ['Response'],
        'Middleware' => ['Core', 'Exception', 'Response'],
        'Router'     => ['Core'],
        'Core'       => ['Exception', 'Middleware', 'Router'],
        'Container'  => ['Config', '+Core', '+Handler'],
    ]);
