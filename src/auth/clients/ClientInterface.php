<?php

namespace davidhirtz\yii2\skeleton\auth\clients;

use davidhirtz\yii2\skeleton\models\AuthClient;

/**
 * Interface ClientInterface.
 * @package davidhirtz\yii2\skeleton\auth\clients
 *
 * @property-read array $safeUserAttributes
 * @see ClientInterface::getSafeUserAttributes
 */
interface ClientInterface extends \yii\authclient\ClientInterface
{
    /**
     * @return string
     */
    public function getAuthData();

    /**
     * @return array
     */
    public function getSafeUserAttributes();

    /**
     * @param AuthClient $client
     * @return string
     */
    public static function getDisplayName($client);

    /**
     * @param AuthClient $client
     * @return string
     */
    public static function getExternalUrl($client);
}
