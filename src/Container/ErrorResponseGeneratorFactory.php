<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ErrorResponseGeneratorFactory
{
    /**
     * @param ContainerInterface $container
     * @return ErrorResponseGenerator
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];

        $debug = isset($config['debug']) ? $config['debug'] : false;

        $template = isset($config['mezzio']['error_handler']['template_error'])
            ? $config['mezzio']['error_handler']['template_error']
            : ErrorResponseGenerator::TEMPLATE_DEFAULT;

        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);

        return new ErrorResponseGenerator($debug, $renderer, $template);
    }
}
