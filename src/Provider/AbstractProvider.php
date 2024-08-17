<?php

declare(strict_types=1);

namespace Paramon\ExampleClient\Provider;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

abstract class AbstractProvider
{
    protected const
        HEADERS = "headers",
        BODY = "body"
    ;

    public function __construct(
        protected ClientInterface $client,
        protected RequestFactoryInterface $requestFactory,
        protected StreamFactoryInterface $streamFactory
    ) {
    }

    protected function sendRequest(string $method, string $uri, array $options): ResponseInterface
    {
        $request = $this->applyRequestOptions(
            $this->requestFactory->createRequest($method, static::getHost() . $uri),
            $options
        );

        return $this->client->sendRequest($request);
    }

    protected function applyRequestOptions(RequestInterface $request, array $options): RequestInterface
    {
        foreach ($options[self::HEADERS] ?? [] as $headerName => $header) {
            $request = $request->withHeader($headerName, $header);
        }
        if ($body = $options[self::BODY] ?? "") {
            $request = $request->withBody($this->getBody($body));
        }

        return $request;
    }

    protected function getParsedBody(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();

        return $body ? json_decode($body, true) : [];
    }

    abstract protected static function getHost(): string;

    /**
     * @return StreamInterface
     *
     * @throws JsonException
     */
    private function getBody(array|string $body): StreamInterface
    {
        $body = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;

        return $this->streamFactory->createStream($body);
    }
}
