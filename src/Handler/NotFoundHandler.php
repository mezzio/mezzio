<?php

declare(strict_types=1);

namespace Mezzio\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function sprintf;

class NotFoundHandler implements RequestHandlerInterface
{
    public const TEMPLATE_DEFAULT = 'error::404';
    public const LAYOUT_DEFAULT = 'layout::default';

    /**
     * @var TemplateRendererInterface|null
     */
    private $renderer;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $layout;

    /**
     * @todo Allow nullable $layout
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        TemplateRendererInterface $renderer = null,
        string $template = self::TEMPLATE_DEFAULT,
        string $layout = self::LAYOUT_DEFAULT
    ) {
        $this->responseFactory = $responseFactory;
        $this->renderer = $renderer;
        $this->template = $template;
        $this->layout = $layout;
    }

    /**
     * Creates and returns a 404 response.
     *
     * @param ServerRequestInterface $request Passed to internal handler
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->renderer === null) {
            return $this->generatePlainTextResponse($request);
        }

        return $this->generateTemplatedResponse($this->renderer, $request);
    }

    /**
     * Generates a plain text response indicating the request method and URI.
     */
    private function generatePlainTextResponse(ServerRequestInterface $request) : ResponseInterface
    {
        $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);
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
    ) : ResponseInterface {

        $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);
        $response->getBody()->write(
            $renderer->render($this->template, ['request' => $request, 'layout' => $this->layout])
        );

        return $response;
    }
}
