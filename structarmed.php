<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->layer('Core', 'src')
    ->layer('Exception', 'src/Exception')
    ->layer('Response', 'src/Response')
    ->layer('Handler', 'src/Handler')
    ->layer('Middleware', 'src/Middleware')
    ->layer('Router', 'src/Router')
    ->layer('Container', 'src/Container')
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->ruleset([
        'Exception'  => [],
        'Response'   => [],
        'Handler'    => ['Response'],
        'Middleware' => ['Core', 'Exception', 'Response'],
        'Router'     => ['Core'],
        'Core'       => ['Exception', 'Middleware', 'Router'],
        'Container'  => ['+Core', '+Handler'],
    ]);
