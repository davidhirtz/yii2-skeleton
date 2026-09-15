<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web;

use Hirtz\Skeleton\Helpers\FileHelper;
use Hirtz\Skeleton\Upload\Upload;
use Override;
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

            $stream = @fopen($url, 'rb', false, $this->createStreamContext());

            if (!$stream) {
                return null;
            }

            $headers = $this->getResponseHeaders($stream);
            $status = $this->getStatusCode($headers);
            $location = $status >= 300 && $status < 400 ? $this->getLocation($headers) : null;

            if ($location === null) {
                if ($status >= 200 && $status < 300) {
                    return $stream;
                }

                fclose($stream);
                return null;
            }

            fclose($stream);
            $url = $this->resolveLocation($url, $location);

            if ($url === null) {
                return null;
            }
        }

        return null;
    }

    /**
     * The address is resolved here and again by the stream wrapper, so a name answering differently the second
     * time is not covered — the fetch is behind a permission, which is what that residual is weighed against.
     */
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
        }

        return (bool)$ips;
    }

    /**
     * @return list<string>
     */
    protected function resolveHost(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return $ips;
    }

    protected function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);

        if ($packed !== false && strlen($packed) === 16 && str_starts_with($packed, str_repeat("\0", 10) . "\xff\xff")) {
            $ip = (string)inet_ntop(substr($packed, 12));
        }

        return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * @return array<string, mixed>
     */
    protected function createStreamContextOptions(): array
    {
        return [
            'http' => [
                'method' => 'GET',
                'follow_location' => 0,
                'ignore_errors' => true,
                'timeout' => Upload::getComponent()->streamUploadTimeout,
            ],
        ];
    }

    /**
     * @return resource
     */
    protected function createStreamContext()
    {
        return stream_context_create($this->createStreamContextOptions());
    }

    /**
     * @param resource $stream
     * @return list<string>
     */
    protected function getResponseHeaders($stream): array
    {
        $data = stream_get_meta_data($stream)['wrapper_data'] ?? [];

        return is_array($data) ? array_values(array_filter($data, is_string(...))) : [];
    }

    /**
     * @param list<string> $headers
     */
    protected function getStatusCode(array $headers): int
    {
        $status = 0;

        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
                $status = (int)$matches[1];
            }
        }

        return $status;
    }

    /**
     * @param list<string> $headers
     */
    protected function getLocation(array $headers): ?string
    {
        $location = null;

        foreach ($headers as $header) {
            if (stripos($header, 'location:') === 0) {
                $location = trim(substr($header, 9));
            }
        }

        return $location ?: null;
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
