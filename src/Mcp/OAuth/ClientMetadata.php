<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

readonly class ClientMetadata
{
    /**
     * @param list<string> $redirectUris
     */
    public function __construct(
        public string $clientId,
        public ?string $clientName,
        public array $redirectUris,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     * @throws OAuthException
     */
    public static function fromArray(string $clientId, array $data): self
    {
        if (($data['client_id'] ?? null) !== $clientId) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The document names another client_id.');
        }

        if (($data['token_endpoint_auth_method'] ?? 'none') !== 'none') {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'Only public clients are supported.');
        }

        $redirectUris = $data['redirect_uris'] ?? null;

        if (!is_array($redirectUris) || !$redirectUris) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The document lists no redirect_uris.');
        }

        foreach ($redirectUris as $uri) {
            if (!is_string($uri) || !RedirectUri::isValid($uri)) {
                throw new OAuthException(OAuthException::INVALID_CLIENT, 'The document lists an invalid redirect URI.');
            }
        }

        $name = $data['client_name'] ?? null;
        $name = is_string($name) ? mb_substr(trim($name), 0, 100) : null;

        return new self($clientId, $name ?: null, array_values($redirectUris));
    }

    public function allowsRedirectUri(string $uri): bool
    {
        foreach ($this->redirectUris as $registered) {
            if (RedirectUri::matches($registered, $uri)) {
                return true;
            }
        }

        return false;
    }

    public function getHost(): string
    {
        return (string)parse_url($this->clientId, PHP_URL_HOST);
    }
}
