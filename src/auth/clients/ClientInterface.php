<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Auth\Clients;

use Hirtz\Skeleton\Models\AuthClient;

/**
 * @property-read array $safeUserAttributes {@see ClientInterface::getSafeUserAttributes()}
 */
interface ClientInterface extends \yii\authclient\ClientInterface
{
    public function getAuthData(): array;

    public function getSafeUserAttributes(): array;

    public static function getDisplayName(AuthClient $client): string;

    public static function getExternalUrl(AuthClient $client): string;
}
