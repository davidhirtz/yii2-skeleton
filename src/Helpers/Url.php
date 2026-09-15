<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;
use yii\helpers\BaseUrl;

class Url extends BaseUrl
{
    /**
     * @param array<string, mixed> $params
     */
    public static function draft(array|string $params): string
    {
        return Yii::$app->getUrlManager()->createDraftUrl($params);
    }

    /**
     * Trims the surrounding slashes and whitespace and encodes whatever whitespace is left, so two URLs that
     * differ in nothing else compare equal.
     */
    public static function sanitize(false|string|null $url): string
    {
        return $url ? preg_replace('/\s+/', '%20', trim($url, '/ ')) : '';
    }
}
