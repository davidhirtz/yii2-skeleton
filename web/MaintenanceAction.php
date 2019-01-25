<?php

namespace davidhirtz\yii2\skeleton\web;

use Yii;
use yii\base\Action;
use yii\web\HttpException;

/**
 * Class MaintenanceAction
 * @package davidhirtz\yii2\skeleton\web
 */
class MaintenanceAction extends Action
{
	const SERVICE_UNAVAILABLE_HTTP_CODE=503;
	const RETRY_AFTER_SECONDS=30;

	/**
	 * @throws \yii\web\HttpException
	 */
	public function run()
	{
		/**
		 * Set no cache and set retry headers.
		 */
		$headers=Yii::$app->response->headers;
		$headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
		$headers->set('Pragma', 'no-cache');
		$headers->set('Retry-After', self::RETRY_AFTER_SECONDS);

		throw new HttpException(self::SERVICE_UNAVAILABLE_HTTP_CODE, Yii::t('app', 'Temporary down for scheduled maintenance. {site} should be back online shortly.', [
			'site'=>Yii::$app->name,
		]));
	}
}
