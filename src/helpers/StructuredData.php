<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers;

use davidhirtz\yii2\skeleton\web\View;
use yii\helpers\Json;
use yii\helpers\Url;

class StructuredData
{
    /**
     * @param array $links either an array containing the keys `name` and `item`, `label` and `url` as set by
     * {@see View::getBreadcrumbs()} or an associative array with the key being the name and the value being the item.
     */
    public static function breadcrumbList(array $links): string
    {
        $items = [];
        $pos = 1;

        foreach ($links as $name => $item) {
            if (isset($item['label'])) {
                if (!isset($item['url'])) {
                    continue;
                }

                $item = [
                    'name' => $item['label'],
                    'item' => $item['url'],
                ];
            }

            if (!isset($item['item'])) {
                $item = [
                    'name' => $name,
                    'item' => $item,
                ];
            }

            $item['item'] = Url::to($item['item'], true);
            $items[] = ['@type' => 'ListItem', 'position' => $pos++, ...$item];
        }

        return $items ? static::tag(['@type' => 'BreadcrumbList', 'itemListElement' => $items]) : '';
    }

    public static function tag(array $data): string
    {
        return Html::script(Json::htmlEncode(['@context' => 'https://schema.org', ...$data]), [
            'type' => 'application/ld+json',
        ]);
    }
}
