<?php

declare(strict_types=1);

namespace Mezzio\Middleware;

use InvalidArgumentException;
use Laminas\Stratigility\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Webmozart\Assert\Assert;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\RunInterface;

use function gettype;
use function is_object;
use function sprintf;

class WhoopsErrorResponseGenerator
{
    private RunInterface $whoops;

    /**
     * @param RunInterface $whoops
     * @throws InvalidArgumentException If $whoops is not a Run or RunInterface
     *     instance.
     */
    public function __construct($whoops)
    {
        /** @psalm-suppress DocblockTypeContradiction Can be removed with the next major when enforcing argument type */
        if (! $whoops instanceof RunInterface) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a %s or %s instance; received %s',
                static::class,
                Run::class,
                RunInterface::class,
                is_object($whoops) ? $whoops::class : gettype($whoops)
            ));
        }

        $this->whoops = $whoops;
    }

    public function __invoke(
        Throwable $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Walk through all handlers
        foreach ($this->whoops->getHandlers() as $handler) {
            // Add fancy data for the PrettyPageHandler
            if ($handler instanceof PrettyPageHandler) {
                $this->prepareWhoopsHandler($request, $handler);
            }

            // Set Json content type header
            if ($handler instanceof JsonResponseHandler) {
                $contentType = $handler->contentType();

                $response = $response->withHeader('Content-Type', $contentType);
            }
        }

        $response = $response->withStatus(Utils::getStatusCode($e, $response));

        $sendOutputFlag = $this->whoops->writeToOutput();
        $this->whoops->writeToOutput(false);
        $response
            ->getBody()
            ->write($this->whoops->handleException($e));
        $this->whoops->writeToOutput($sendOutputFlag);

        return $response;
    }

    /**
     * Prepare the Whoops page handler with a table displaying request information
     */
    private function prepareWhoopsHandler(ServerRequestInterface $request, PrettyPageHandler $handler): void
    {
        $uri     = $request->getAttribute('originalUri', false) ?: $request->getUri();
        $request = $request->getAttribute('originalRequest', false) ?: $request;

        $serverParams = $request->getServerParams();
        Assert::isMap($serverParams);

        $scriptName = $serverParams['SCRIPT_NAME'] ?? '';

        $handler->addDataTable('Mezzio Application Request', [
            'HTTP Method'            => $request->getMethod(),
            'URI'                    => (string) $uri,
            'Script'                 => $scriptName,
            'Headers'                => $request->getHeaders(),
            'Cookies'                => $request->getCookieParams(),
            'Attributes'             => $request->getAttributes(),
            'Query String Arguments' => $request->getQueryParams(),
            'Body Params'            => $request->getParsedBody(),
        ]);
    }
}
