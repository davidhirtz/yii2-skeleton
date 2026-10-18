<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

use Hirtz\Skeleton\Models\OAuthGrant;

/**
 * An error {@see self::fromParams()} throws is shown on the page: until the client and its redirect URI are known
 * there is nowhere to send one. Once the request exists, {@see self::validate()} names errors to redirect with.
 */
readonly class AuthorizationRequest
{
    /**
     * @param array<string, mixed> $params
     */
    private function __construct(
        public ClientMetadata $client,
        public string $redirectUri,
        public ?string $state,
        public array $params,
    ) {
    }

    /**
     * @param array<array-key, mixed> $params
     * @param list<string> $allowedClientIds
     * @throws OAuthException
     */
    public static function fromParams(array $params, ClientMetadataFetcher $fetcher, array $allowedClientIds = []): self
    {
        $clientId = $params['client_id'] ?? null;
        $redirectUri = $params['redirect_uri'] ?? null;

        if (!is_string($clientId) || !ClientMetadataFetcher::isValidClientId($clientId)) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'The client_id is not an HTTPS URL with a path.');
        }

        if ($allowedClientIds && !array_filter($allowedClientIds, fn (string $prefix): bool => str_starts_with($clientId, $prefix))) {
            throw new OAuthException(OAuthException::INVALID_CLIENT, 'This client is not allowed.');
        }

        $client = $fetcher->fetch($clientId);

        if (!is_string($redirectUri) || !$client->allowsRedirectUri($redirectUri)) {
            throw new OAuthException(OAuthException::INVALID_REQUEST, 'The redirect_uri is not registered for this client.');
        }

        $state = $params['state'] ?? null;

        /** @var array<string, mixed> $params */
        return new self($client, $redirectUri, is_string($state) ? $state : null, $params);
    }

    /**
     * @throws OAuthException
     */
    public function validate(string $resource): void
    {
        if (($this->params['response_type'] ?? null) !== 'code') {
            throw new OAuthException(OAuthException::UNSUPPORTED_RESPONSE_TYPE, 'Only the response_type code is supported.');
        }

        if (($this->params['code_challenge_method'] ?? null) !== 'S256') {
            throw new OAuthException(OAuthException::INVALID_REQUEST, 'PKCE with code_challenge_method S256 is required.');
        }

        if (!preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $this->getCodeChallenge())) {
            throw new OAuthException(OAuthException::INVALID_REQUEST, 'The code_challenge is invalid.');
        }

        if (!ResourceUri::equals($this->getResource() ?? $resource, $resource)) {
            throw new OAuthException(OAuthException::INVALID_TARGET, 'The resource is not this server.');
        }
    }

    public function getCodeChallenge(): string
    {
        $challenge = $this->params['code_challenge'] ?? null;
        return is_string($challenge) ? $challenge : '';
    }

    public function getResource(): ?string
    {
        $resource = $this->params['resource'] ?? null;
        return is_string($resource) && $resource !== '' ? $resource : null;
    }

    /**
     * Scopes this server does not know are ignored, and a request naming neither of its own asks for write access,
     * which is what the `401` challenge names.
     */
    public function getScope(): string
    {
        $scope = $this->params['scope'] ?? null;
        $scopes = is_string($scope) ? explode(' ', $scope) : [];

        return in_array(OAuthGrant::SCOPE_READ, $scopes, true) && !in_array(OAuthGrant::SCOPE_WRITE, $scopes, true)
            ? OAuthGrant::SCOPE_READ
            : OAuthGrant::SCOPE_WRITE;
    }

    /**
     * @param array<string, string> $params
     */
    public function getRedirectUrl(array $params, string $issuer): string
    {
        $params = array_filter([...$params, 'state' => $this->state, 'iss' => $issuer], fn (?string $value): bool => $value !== null);
        $separator = str_contains($this->redirectUri, '?') ? '&' : '?';

        return $this->redirectUri . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
