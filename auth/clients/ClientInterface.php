<?php

namespace davidhirtz\yii2\skeleton\auth\clients;

/**
 * Interface ClientInterface.
 * @package davidhirtz\yii2\skeleton\auth\clients
 *
 * @property-read array $safeUserAttributes
 * @see \davidhirtz\yii2\skeleton\auth\clients\ClientInterface::getSafeUserAttributes()
 */
interface ClientInterface extends \yii\authclient\ClientInterface
{
	/**
	 * @return string
	 */
	public function getAuthData();

	/**
	 *
	 * @return array
	 */
	public function getSafeUserAttributes();

	/**
	 * @param \davidhirtz\yii2\skeleton\models\AuthClient $client
	 * @return string
	 */
	public static function getDisplayName($client);

	/**
	 * @param \davidhirtz\yii2\skeleton\models\AuthClient $client
	 * @return string
	 */
	public static function getExternalUrl($client);
}