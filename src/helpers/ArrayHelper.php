<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use SimpleXMLElement;
use yii\helpers\BaseArrayHelper;

class ArrayHelper extends BaseArrayHelper
{
    /**
     * @noinspection PhpUnused
     */
    public static function replaceValue(array &$array, string $value, mixed $replacement): void
    {
        if (($key = array_search($value, $array, true)) !== false) {
            $array[$key] = $replacement;
        }
    }

    public static function setDefaultValue(array &$array, int|string $key, mixed $value): void
    {
        if (!static::keyExists($key, $array)) {
            $array[$key] = $value;
        }
    }

    /**
     * @noinspection PhpUnused
     */
    public static function setDefaultValues(array &$array, array $values): void
    {
        foreach ($values as $key => $value) {
            static::setDefaultValue($array, $key, $value);
        }
    }

    public static function cacheStringToArray(
        array|string|null $string,
        array|int|string $values = [],
        string $separator = ','
    ): array {
        if (!is_array($string)) {
            $string = $string ? explode($separator, $string) : [];
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        return array_unique(array_filter([...$string, ...$values]));
    }

    public static function createCacheString(
        array $array,
        string $separator = ',',
        ?string $default = null
    ): ?string {
        return $array ? implode($separator, $array) : $default;
    }

    /**
     * @noinspection PhpUnused
     */
    public static function simpleXmlToArray(?SimpleXMLElement $xml): array
    {
        $namespace = $xml?->getDocNamespaces(true);
        $namespace[null] = null;

        $children = [];
        $attributes = [];

        $name = $xml->getName();
        $text = trim((string)$xml);

        if (strlen($text) <= 0) {
            $text = null;
        }

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (is_object($xml)) {
            foreach ($namespace as $ns => $nsUrl) {
                // Attributes.
                $_attributes = $xml->attributes($ns, true);

                foreach ($_attributes as $attribute => $value) {
                    $attribute = trim($attribute);
                    $value = trim((string)$value);

                    if (!empty($ns)) {
                        $attribute = $ns . ':' . $attribute;
                    }

                    $attributes[$attribute] = $value;
                }

                // Children.
                $_children = $xml->children($ns, true);

                foreach ($_children as $child => $value) {
                    if (!empty($ns)) {
                        $child = $ns . ':' . $child;
                    }

                    $children[$child][] = static::simpleXmlToArray($value);
                }
            }
        }

        return [
            'name' => $name,
            'text' => $text,
            'attributes' => $attributes,
            'children' => $children
        ];
    }
}
