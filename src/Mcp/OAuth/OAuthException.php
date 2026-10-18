<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

use Exception;

class OAuthException extends Exception
{
    final public const string ACCESS_DENIED = 'access_denied';
    final public const string INVALID_CLIENT = 'invalid_client';
    final public const string INVALID_GRANT = 'invalid_grant';
    final public const string INVALID_REQUEST = 'invalid_request';
    final public const string INVALID_SCOPE = 'invalid_scope';
    final public const string INVALID_TARGET = 'invalid_target';
    final public const string SERVER_ERROR = 'server_error';
    final public const string UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';
    final public const string UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    public function __construct(public readonly string $error, string $description = '')
    {
        parent::__construct($description);
    }

    public function getStatusCode(): int
    {
        return $this->error === self::INVALID_CLIENT ? 401 : 400;
    }

    /**
     * @return array{error: string, error_description?: string}
     */
    public function toArray(): array
    {
        $data = ['error' => $this->error];

        if ($this->getMessage() !== '') {
            $data['error_description'] = $this->getMessage();
        }

        return $data;
    }
}
