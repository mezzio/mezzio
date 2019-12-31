<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Exception;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Container\ServerRequestErrorResponseGeneratorFactory;
use Mezzio\Middleware\ErrorResponseGenerator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Throwable;

class ServerRequestErrorResponseGeneratorFactoryTest extends TestCase
{
    public function testFactoryGeneratesCallable()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $factory = new ServerRequestErrorResponseGeneratorFactory();

        $generator = $factory($container->reveal());

        $this->assertInternalType('callable', $generator);

        return [$generator, $container];
    }

    /**
     * @depends testFactoryGeneratesCallable
     */
    public function testGeneratedCallableWrapsErrorResponseGeneratorService(array $deps)
    {
        $generator = array_shift($deps);
        $container = array_shift($deps);

        $exception = new Exception();

        $proxiedGenerator = function (Throwable $e, ServerRequest $request, Response $response) use ($exception) {
            Assert::assertSame($exception, $e);
            return $response;
        };

        $container->get(ErrorResponseGenerator::class)->willReturn($proxiedGenerator);

        $this->assertInstanceOf(Response::class, $generator($exception));
    }
}
