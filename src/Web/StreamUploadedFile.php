<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Helpers\NetworkHelper;
use Hirtz\Skeleton\Upload\Upload;
use Override;
use Psr\Http\Message\StreamInterface;
use Yii;

/**
 * An upload the server fetches from a URL the request supplied, so what may be asked for is policy rather than a
 * detail of the transfer: {@see Upload::$enableStreamUploads} and the `streamUpload` settings beside it decide
 * whether it happens at all, how far it reaches and how much it may cost. A path the application names goes
 * through {@see CopiedUploadedFile} instead — none of this applies to one.
 */
class StreamUploadedFile extends AbstractUploadedFile
{
    public ?string $url = null;

    /**
     * @var array<string, string> the address each host was checked at, which the transfer is pinned to
     */
    private array $checkedIps = [];

    #[Override]
    protected function saveTemporaryFile(): void
    {
        $url = $this->prepareUrl();

        if ($url === null) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $this->url = $url;
        $this->name = basename((string)parse_url($url, PHP_URL_PATH));

        $stream = $this->openUrl($url);

        if ($stream === null) {
            $this->error = UPLOAD_ERR_NO_FILE;
            return;
        }

        $this->copyToTemporaryFile($stream, Upload::getComponent()->maxStreamUploadSize);
        fclose($stream);
    }

    /**
     * @return string|null the URL to fetch, or `null` for one that must not be fetched at all
     */
    protected function prepareUrl(): ?string
    {
        if (!$this->url || !Upload::getComponent()->enableStreamUploads) {
            return null;
        }

        $url = FileHelper::encodeUrl($this->url);

        return $this->isAllowedUrl($url) ? $url : null;
    }

    /**
     * A refusal answers the same {@see UPLOAD_ERR_NO_FILE} as a target that said nothing, so the error code
     * reports no more about the network than the account already knows.
     *
     * @return resource|null
     */
    protected function openUrl(string $url)
    {
        $maxRedirects = Upload::getComponent()->maxStreamUploadRedirects;

        for ($hop = 0; $hop <= $maxRedirects; ++$hop) {
            if (!$this->isAllowedUrl($url)) {
                return null;
            }

            $resource = Utils::tryFopen('php://temp', 'w+b');
            $sink = Utils::streamFor($resource);

            try {
                $response = $this->getClient()->request('GET', $url, $this->getRequestOptions($url, $sink));
            } catch (GuzzleException $exception) {
                $sink->detach();

                // Guzzle may already have closed the sink (a timeout on some cURL versions)
                if (!is_resource($resource)) {
                    Yii::warning("Fetching \"$url\" failed: {$exception->getMessage()}", __METHOD__);
                    return null;
                }

                // The transfer aborted past the limit: the copy reports the size
                if (ftell($resource) > Upload::getComponent()->maxStreamUploadSize) {
                    rewind($resource);
                    return $resource;
                }

                fclose($resource);
                Yii::warning("Fetching \"$url\" failed: {$exception->getMessage()}", __METHOD__);
                return null;
            }

            $sink->detach();
            $status = $response->getStatusCode();
            $location = $status >= 300 && $status < 400 ? $response->getHeaderLine('Location') : '';

            if ($location === '') {
                if ($status >= 200 && $status < 300) {
                    rewind($resource);
                    return $resource;
                }

                fclose($resource);
                return null;
            }

            fclose($resource);
            $url = $this->resolveLocation($url, $location);

            if ($url === null) {
                return null;
            }
        }

        return null;
    }

    /**
     * cURL connects to the address {@see isAllowedUrl()} checked, and a write refused once the body is past
     * {@see Upload::$maxStreamUploadSize} aborts the transfer.
     *
     * @return array<string, mixed>
     */
    protected function getRequestOptions(string $url, StreamInterface $sink): array
    {
        $upload = Upload::getComponent();
        $maxSize = $upload->maxStreamUploadSize;

        $options = [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::CONNECT_TIMEOUT => $upload->streamUploadTimeout,
            RequestOptions::TIMEOUT => $upload->streamUploadTimeout,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::SINK => FnStream::decorate($sink, [
                'write' => static fn (string $data): int => (int)$sink->getSize() > $maxSize ? 0 : $sink->write($data),
            ]),
        ];

        $host = (string)parse_url($url, PHP_URL_HOST);
        $ip = $this->checkedIps[$host] ?? null;

        if ($ip !== null && defined('CURLOPT_RESOLVE')) {
            $port = parse_url($url, PHP_URL_PORT) ?: (str_starts_with(strtolower($url), 'https:') ? 443 : 80);
            $options['curl'] = [CURLOPT_RESOLVE => ["$host:$port:" . (str_contains($ip, ':') ? "[$ip]" : $ip)]];
        }

        return $options;
    }

    protected function isAllowedUrl(string $url): bool
    {
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        $host = (string)parse_url($url, PHP_URL_HOST);

        if (!$host || ($scheme !== 'http' && $scheme !== 'https')) {
            Yii::warning("Refused to fetch \"$url\"", __METHOD__);
            return false;
        }

        if (Upload::getComponent()->allowPrivateStreamUploadHosts) {
            return true;
        }

        $ips = $this->resolveHost($host);

        foreach ($ips as $ip) {
            if (!$this->isPublicIp($ip)) {
                Yii::warning("Refused to fetch \"$url\", \"$host\" resolves to \"$ip\"", __METHOD__);
                return false;
            }
        }

        if (!$ips) {
            Yii::warning("Refused to fetch \"$url\", \"$host\" does not resolve", __METHOD__);
            return false;
        }

        $this->checkedIps[$host] = $ips[0];
        return true;
    }

    /**
     * @return list<string>
     */
    protected function resolveHost(string $host): array
    {
        return NetworkHelper::resolveHost($host);
    }

    protected function isPublicIp(string $ip): bool
    {
        return NetworkHelper::isPublicIp($ip);
    }

    protected function getClient(): ClientInterface
    {
        return new Client();
    }

    protected function resolveLocation(string $url, string $location): ?string
    {
        $locationScheme = parse_url($location, PHP_URL_SCHEME);

        if ($locationScheme === false) {
            return null;
        }

        if ($locationScheme !== null) {
            return FileHelper::encodeUrl($location);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($scheme) || !is_string($host)) {
            return null;
        }

        if (str_starts_with($location, '//')) {
            return FileHelper::encodeUrl("$scheme:$location");
        }

        $port = parse_url($url, PHP_URL_PORT);
        $authority = "$scheme://$host" . ($port ? ":$port" : '');

        if (str_starts_with($location, '/')) {
            return FileHelper::encodeUrl($authority . $location);
        }

        $path = (string)parse_url($url, PHP_URL_PATH);
        $directory = substr($path, 0, (int)strrpos($path, '/') + 1);

        return FileHelper::encodeUrl($authority . ($directory ?: '/') . $location);
    }
}
