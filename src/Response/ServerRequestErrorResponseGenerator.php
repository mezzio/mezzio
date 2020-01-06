<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Response;

use Laminas\Stratigility\Utils;
use Mezzio\Template\TemplateRendererInterface;
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
     * Factory capable of generating a ResponseInterface instance.
     *
     * @var callable
     */
    private $responseFactory;

    public function __construct(
        callable $responseFactory,
        bool $isDevelopmentMode = false,
        ?TemplateRendererInterface $renderer = null,
        string $template = self::TEMPLATE_DEFAULT
    ) {
        $this->responseFactory = function () use ($responseFactory): ResponseInterface {
            return $responseFactory();
        };

        $this->debug    = $isDevelopmentMode;
        $this->renderer = $renderer;
        $this->template = $template;
    }

    public function __invoke(Throwable $e): ResponseInterface
    {
        $response = ($this->responseFactory)();
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
