<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

interface I18nAttributeInterface
{
    /**
     * @return list<string> `i18nAttributes` plus the translatable custom attributes of the model's current state
     */
    public function getI18nAttributes(): array;

    public function getI18nAttribute(string $attribute, ?string $language = null, bool $fallback = false): mixed;
    public function getI18nAttributeName(string $attribute, ?string $language = null, bool $fallback = false): string;
    /**
     * @param list<string>|null $languages
     * @return array<string, string>
     */
    public function getI18nAttributeNames(string $attribute, ?array $languages = null): array;
    /**
     * @param list<string>|string $attributes
     * @param list<string>|null $languages
     * @return list<string>
     */
    public function getI18nAttributesNames(array|string $attributes, ?array $languages = null): array;
    /**
     * @param list<array<mixed>> $rules
     * @return list<array<mixed>>
     */
    public function getI18nRules(array $rules): array;
    public function isI18nAttribute(string $attribute): bool;
}
