<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Registry;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Yii;

/**
 * The transport behind `registry/push`, kept apart from the command so a project can push from elsewhere. The
 * key travels in the `Authorization` header only; it is never logged, `_SERVER.HTTP_AUTHORIZATION` being masked on
 * the receiving side already.
 */
class RegistryClient
{
    public string $url;
    public string $key;
    public int $connectTimeout = 5;
    public int $timeout = 10;

    /**
     * @var ClientInterface|null the test seam: a client built over a `MockHandler`
     */
    public ?ClientInterface $client = null;

    public function push(Report $report): RegistryResponse
    {
        try {
            $response = $this->getClient()->request('POST', $this->url, [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer $this->key",
                ],
                RequestOptions::JSON => $report,
                RequestOptions::CONNECT_TIMEOUT => $this->connectTimeout,
                RequestOptions::TIMEOUT => $this->timeout,
                RequestOptions::HTTP_ERRORS => false,
            ]);
        } catch (GuzzleException $exception) {
            Yii::warning("Registry push to $this->url failed: {$exception->getMessage()}", __METHOD__);
            return RegistryResponse::fromException($exception);
        }

        Yii::info("Registry push to $this->url answered {$response->getStatusCode()}", __METHOD__);
        return RegistryResponse::fromResponse($response);
    }

    public function getHost(): string
    {
        return parse_url($this->url, PHP_URL_HOST) ?: $this->url;
    }

    protected function getClient(): ClientInterface
    {
        return $this->client ??= new Client();
    }
}
