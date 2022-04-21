<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Mezzio\ApplicationPipeline;
use Psr\Container\ContainerInterface;

class ApplicationPipelineFactory
{
    public function __invoke(ContainerInterface $container): MiddlewarePipeInterface
    {
        return new ApplicationPipeline(new MiddlewarePipe());
    }
}
