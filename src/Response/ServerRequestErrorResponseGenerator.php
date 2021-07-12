<?php

declare(strict_types=1);

namespace Mezzio\Response;

use Laminas\Stratigility\Utils;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Generates a response for use when the server request factory fails.
 */
class ServerRequestErrorResponseGenerator
{
    use ErrorResponseGeneratorTrait;

    public const TEMPLATE_DEFAULT = 'error::error';

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        bool $isDevelopmentMode = false,
        TemplateRendererInterface $renderer = null,
        string $template = self::TEMPLATE_DEFAULT
    ) {
        $this->responseFactory = $responseFactory;

        $this->debug     = $isDevelopmentMode;
        $this->renderer  = $renderer;
        $this->template  = $template;
    }

    public function __invoke(Throwable $e) : ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response = $response->withStatus(Utils::getStatusCode($e, $response));

        if ($this->renderer) {
            return $this->prepareTemplatedResponse(
                $e,
                $this->renderer,
                [
                    'response' => $response,
                    'status'   => $response->getStatusCode(),
                    'reason'   => $response->getReasonPhrase(),
                ],
                $this->debug,
                $response
            );
        }

        return $this->prepareDefaultResponse($e, $this->debug, $response);
    }
}
