<?php

declare(strict_types=1);

namespace PhpCfdi\CfdiSatScraper\Tests\CaptchaLocalResolver;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CaptchaLocalResolverClient
{
    /** @var int it will not wait for an answer for more than 90 seconds */
    private const MAX_TIMEOUT = 90;

    /** @var int it will not use a port number greater than 65535 */
    public const MAX_PORTNUMBER = 65535;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var int */
    private $timeout;

    /** @var ClientInterface */
    private $httpClient;

    public function __construct(string $host, int $port, int $timeout, ClientInterface $httpClient)
    {
        $this->host = $host ?: 'localhost';
        $this->port = min(self::MAX_PORTNUMBER, max(0, $port)) ?: 80;
        $this->timeout = min(self::MAX_TIMEOUT, max(0, $timeout)) ?: self::MAX_TIMEOUT;
        $this->httpClient = $httpClient;
    }

    public function resolveImage(string $image): string
    {
        $code = $this->sendImage($image);
        if ('' === $code) {
            return ''; // Unable to send image
        }
        $waitUntil = time() + $this->getTimeout();
        do {
            $result = $this->checkCode($code);
            if ('' !== $result) { // it found an answer !!
                break;
            }
            if (time() > $waitUntil) {
                throw new RuntimeException("Unable to get an answer after {$this->getTimeout()} seconds");
            }
            sleep(1);
        } while (true);

        return $result;
    }

    public function sendImage(string $image): string
    {
        $uri = $this->buildUri('send-image'); // TODO
        $response = $this->post($uri, ['image' => $image]);
        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException("Unable to send image to $uri");
        }
        $contents = $response->getBody()->getContents();
        $data = json_decode($contents, true);
        return strval($data['code'] ?? '');
    }

    public function checkCode(string $code): string
    {
        $uri = $this->buildUri('obtain-decoded');
        $response = $this->post($uri, ['code' => $code]);
        if (404 === $response->getStatusCode()) {
            throw new RuntimeException("Unable to retrieve answer for non-existent code $code");
        }
        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException("Unable to check code for $uri");
        }
        $contents = $response->getBody()->getContents();
        $data = json_decode($contents, true);
        return strval($data['answer'] ?? '');
    }

    public function buildUri(string $action): string
    {
        return sprintf('http://%s:%d/%s', $this->getHost(), $this->getPort(), $action);
    }

    private function post(string $uri, array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', $uri, [
            'form_params' => $data,
            'timeout' => $this->getTimeout(),
        ]);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }
}
