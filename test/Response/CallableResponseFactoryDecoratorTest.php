<?php

declare(strict_types=1);

namespace MezzioTest\Response;

use Mezzio\Response\CallableResponseFactoryDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class CallableResponseFactoryDecoratorTest extends TestCase
{
    /** @var MockObject&ResponseInterface */
    private $response;

    private CallableResponseFactoryDecorator $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = $this->createMock(ResponseInterface::class);
        $this->factory  = new CallableResponseFactoryDecorator(fn(): ResponseInterface => $this->response);
    }

    public function testWillPassStatusCodeAndPhraseToCallable(): void
    {
        $this->response
            ->expects(self::once())
            ->method('withStatus')
            ->with(500, 'Foo')
            ->willReturnSelf();

        $this->factory->createResponse(500, 'Foo');
    }

    public function testWillReturnSameResponseInstance(): void
    {
        $this->response
            ->expects(self::once())
            ->method('withStatus')
            ->willReturnSelf();

        self::assertEquals($this->response, $this->factory->createResponse());
    }
}
