<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers;

use Yii;
use yii\helpers\BaseUrl;

class Url extends BaseUrl
{
    public static function draft(string $url): string
    {
        $request = Yii::$app->getRequest();

        if ($request->draftSubdomain === false) {
            return $url;
        }

        return !$request->getIsDraft() || !str_contains($url, $request->draftSubdomain)
            ? preg_replace('#^((https?://)(www.)?)#', "$2$request->draftSubdomain.", $url)
            : $url;
    }
}
