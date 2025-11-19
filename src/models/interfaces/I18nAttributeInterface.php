<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\interfaces;

interface I18nAttributeInterface
{
    public function getI18nAttribute(string $attribute, ?string $language = null): mixed;
    public function getI18nAttributeName(string $attribute, ?string $language = null): string;
    public function getI18nAttributeNames(string $attribute, ?string $languages = null): array;
    public function getI18nAttributesNames(array|string $attributes, ?array $languages = null): array;
    public function getI18nRules(array $rules): array;
    public function isI18nAttribute(string $attribute): bool;
}