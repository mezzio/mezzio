<?php

declare(strict_types=1);

namespace Mezzio\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Response\CallableResponseFactoryDecorator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_callable;
use function sprintf;

class NotFoundHandler implements RequestHandlerInterface
{
    public const TEMPLATE_DEFAULT = 'error::404';
    public const LAYOUT_DEFAULT   = 'layout::default';

    private ResponseFactoryInterface $responseFactory;

    /**
     * @todo Allow nullable $layout
     * @param callable|ResponseFactoryInterface $responseFactory
     * @psalm-param (callable():ResponseInterface)|ResponseFactoryInterface $responseFactory
     */
    public function __construct(
        $responseFactory,
        private ?TemplateRendererInterface $renderer = null,
        private string $template = self::TEMPLATE_DEFAULT,
        private string $layout = self::LAYOUT_DEFAULT
    ) {
        if (is_callable($responseFactory)) {
            $responseFactory = new CallableResponseFactoryDecorator($responseFactory);
        }

        $this->responseFactory = $responseFactory;
    }

    /**
     * Creates and returns a 404 response.
     *
     * @param ServerRequestInterface $request Passed to internal handler
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->renderer === null) {
            return $this->generatePlainTextResponse($request);
        }

        return $this->generateTemplatedResponse($this->renderer, $request);
    }

    /**
     * Generates a plain text response indicating the request method and URI.
     */
    private function generatePlainTextResponse(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
        $response->getBody()
            ->write(sprintf(
                'Cannot %s %s',
                $request->getMethod(),
                (string) $request->getUri()
            ));

        return $response;
    }

    /**
     * Generates a response using a template.
     *
     * Template will receive the current request via the "request" variable.
     */
    private function generateTemplatedResponse(
        TemplateRendererInterface $renderer,
        ServerRequestInterface $request
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse()->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
        $response->getBody()->write(
            $renderer->render($this->template, ['request' => $request, 'layout' => $this->layout])
        );

        return $response;
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }
}
