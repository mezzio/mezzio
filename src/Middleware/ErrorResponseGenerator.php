<?php

declare(strict_types=1);

namespace Mezzio\Middleware;

use Laminas\Stratigility\Utils;
use Mezzio\Response\ErrorResponseGeneratorTrait;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ErrorResponseGenerator
{
    use ErrorResponseGeneratorTrait;

    public const TEMPLATE_DEFAULT = 'error::error';
    public const LAYOUT_DEFAULT   = 'layout::default';

    /**
     * @todo Allow nullable $layout
     */
    public function __construct(
        bool $isDevelopmentMode = false,
        ?TemplateRendererInterface $renderer = null,
        string $template = self::TEMPLATE_DEFAULT,
        string $layout = self::LAYOUT_DEFAULT
    ) {
        $this->debug    = $isDevelopmentMode;
        $this->renderer = $renderer;
        $this->template = $template;
        $this->layout   = $layout;
    }

    public function __invoke(
        Throwable $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $response = $response->withStatus(Utils::getStatusCode($e, $response));

        if ($this->renderer) {
            return $this->prepareTemplatedResponse(
                $e,
                $this->renderer,
                [
                    'response' => $response,
                    'request'  => $request,
                    'uri'      => (string) $request->getUri(),
                    'status'   => $response->getStatusCode(),
                    'reason'   => $response->getReasonPhrase(),
                    'layout'   => $this->layout,
                ],
                $this->debug,
                $response
            );
        }

        return $this->prepareDefaultResponse($e, $this->debug, $response);
    }
}
