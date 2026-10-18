<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Hirtz\Skeleton\Helpers\NetworkHelper;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Yii;

/**
 * The `client_id` is a URL any visitor of the authorize page supplies, so it is fetched under the same address policy
 * as a URL upload, pinned to the address that was checked, without following redirects.
 */
class ClientMetadataFetcher
{
    public int $connectTimeout = 3;
    public int $timeout = 5;
    public int $maxSize = 5120;

    /**
     * @var int seconds a document is kept when its response sets no `max-age`, which is also the most it is kept
     */
    public int $defaultCacheDuration = 3600;
    public int $maxCacheDuration = 86400;

    /**
     * @var ClientInterface|null the test seam: a client built over a `MockHandler`
     */
    public ?ClientInterface $client = null;

    /**
     * @throws OAuthException
     */
    public function fetch(string $clientId): ClientMetadata
    {
        $cache = Yii::$app->getCache();
        $key = [self::class, $clientId];
        $data = $cache?->get($key);

        if (!is_array($data)) {
            [$data, $duration] = $this->request($clientId);

            if ($duration > 0) {
                $cache?->set($key, $data, $duration);
            }
        }

        return ClientMetadata::fromArray($clientId, $data);
    }

    public static function isValidClientId(string $clientId): bool
    {
        $parts = parse_url($clientId);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && isset($parts['host'])
            && ($parts['path'] ?? '/') !== '/'
            && !isset($parts['user'])
            && !isset($parts['fragment'])
            && strlen($clientId) <= 255;
    }

    /**
     * @return array{array<array-key, mixed>, int}
     * @throws OAuthException
     */
    protected function request(string $clientId): array
    {
        if (!self::isValidClientId($clientId)) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The client_id is not an HTTPS URL with a path.');
        }

        $host = (string)parse_url($clientId, PHP_URL_HOST);
        $ip = $this->resolvePublicIp($host);

        if ($ip === null) {
            Yii::warning("Refused to fetch the client metadata \"$clientId\"", __METHOD__);
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The client_id does not resolve to a public address.');
        }

        $options = [
            RequestOptions::HEADERS => ['Accept' => 'application/json'],
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::CONNECT_TIMEOUT => $this->connectTimeout,
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::SINK => $this->createSink(),
        ];

        // Without cURL the name is resolved a second time, the residual `StreamUploadedFile` accepts as well
        if (defined('CURLOPT_RESOLVE')) {
            $port = parse_url($clientId, PHP_URL_PORT) ?: 443;
            $options['curl'] = [CURLOPT_RESOLVE => ["$host:$port:" . (str_contains($ip, ':') ? "[$ip]" : $ip)]];
        }

        try {
            $response = $this->getClient()->request('GET', $clientId, $options);
            $body = $this->readBody($response);
        } catch (GuzzleException|RuntimeException $exception) {
            Yii::warning("Fetching the client metadata \"$clientId\" failed: {$exception->getMessage()}", __METHOD__);
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The client metadata could not be fetched.');
        }

        if ($response->getStatusCode() !== 200) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, "The client metadata answered {$response->getStatusCode()}.");
        }

        if (strlen($body) > $this->maxSize) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The client metadata is too large.');
        }

        try {
            $data = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $data = null;
        }

        if (!is_array($data)) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The client metadata is not a JSON object.');
        }

        return [$data, $this->getCacheDuration($response)];
    }

    /**
     * A write refused past `maxSize` makes cURL abort the transfer, which `stream` cannot do without dropping
     * the `CURLOPT_RESOLVE` pin, as Guzzle then fetches through a PHP stream.
     */
    protected function createSink(): StreamInterface
    {
        $sink = Utils::streamFor(Utils::tryFopen('php://temp', 'w+'));
        $maxSize = $this->maxSize;

        return FnStream::decorate($sink, [
            'write' => static fn (string $data): int => (int)$sink->getSize() + strlen($data) > $maxSize
                ? 0
                : $sink->write($data),
        ]);
    }

    protected function readBody(ResponseInterface $response): string
    {
        $stream = $response->getBody();
        $stream->rewind();

        return $stream->read($this->maxSize + 1);
    }

    protected function resolvePublicIp(string $host): ?string
    {
        $ips = NetworkHelper::resolveHost($host);

        foreach ($ips as $ip) {
            if (!NetworkHelper::isPublicIp($ip)) {
                return null;
            }
        }

        return $ips[0] ?? null;
    }

    protected function getCacheDuration(ResponseInterface $response): int
    {
        $header = strtolower($response->getHeaderLine('Cache-Control'));

        if (str_contains($header, 'no-store') || str_contains($header, 'no-cache')) {
            return 0;
        }

        return preg_match('/max-age=(\d+)/', $header, $matches)
            ? min((int)$matches[1], $this->maxCacheDuration)
            : $this->defaultCacheDuration;
    }

    protected function getClient(): ClientInterface
    {
        return $this->client ??= new Client();
    }
}
