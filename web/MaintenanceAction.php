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
    const SERVICE_UNAVAILABLE_HTTP_CODE = 503;
    const RETRY_AFTER_SECONDS = 30;

    /**
     * @throws \yii\web\HttpException
     */
    public function run()
    {
        $headers = Yii::$app->getResponse()->getHeaders();
        $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $headers->set('Pragma', 'no-cache');
        $headers->set('Retry-After', self::RETRY_AFTER_SECONDS);

        throw new HttpException(self::SERVICE_UNAVAILABLE_HTTP_CODE, Yii::t('app', 'Temporary down for scheduled maintenance. {site} will be back online shortly.', [
            'site' => Yii::$app->name,
        ]));
    }
}
