<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Registry;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final readonly class RegistryResponse
{
    /**
     * @param array<string, mixed> $data the decoded JSON body, empty where there was none
     * @param string|null $error the transport error, for a request that never got an answer
     */
    public function __construct(
        public int $status,
        public array $data = [],
        public ?string $error = null,
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        try {
            $data = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $data = [];
        }

        return new self($response->getStatusCode(), is_array($data) ? $data : []);
    }

    public static function fromException(GuzzleException $exception): self
    {
        return new self(0, [], $exception->getMessage());
    }

    public function isSuccess(): bool
    {
        return $this->error === null && $this->status >= 200 && $this->status < 300;
    }

    /**
     * The registry's own sentence where it sent one — Yii's JSON error response carries it as `message` — else
     * the transport error, else the bare status.
     */
    public function getMessage(): string
    {
        $message = $this->data['message'] ?? null;

        if (is_string($message) && $message !== '') {
            return $this->status ? "Registry answered $this->status: $message" : $message;
        }

        return $this->error ?? "Registry answered $this->status";
    }
}
