<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;
use yii\helpers\BaseUrl;

class Url extends BaseUrl
{
    public static function draft(array|string $params): string
    {
        return Yii::$app->getUrlManager()->createDraftUrl($params);
    }

    /**
     * @param array<string, mixed> $params
     * @param bool|string|null $scheme `true` or a scheme name returns an absolute URL, as in {@see static::to()}
     */
    public static function route(string $name, array $params = [], bool|string|null $scheme = null): string
    {
        $generator = Yii::$app->getUrlGenerator();

        if ($scheme === null || $scheme === false) {
            return $generator->generate($name, $params);
        }

        return $generator->generateAbsolute($name, $params, is_string($scheme) ? $scheme : null);
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function draftRoute(string $name, array $params = []): string
    {
        return Yii::$app->getUrlGenerator()->generateDraft($name, $params);
    }
}
