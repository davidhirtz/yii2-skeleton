<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers;

use Yii;
use yii\helpers\BaseUrl;

class Url extends BaseUrl
{
    public static function draft(array|string $params): string
    {
        return Yii::$app->getUrlManager()->createDraftUrl($params);
    }
}
