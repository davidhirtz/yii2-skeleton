<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

/**
 * Names the default custom attributes of a model that are stored per language. The values live in the JSON column
 * under their suffixed name, so this is not {@see I18nAttributesTrait::$i18nAttributes}, which names columns.
 */
trait TranslatableAttributesTrait
{
    /**
     * @var list<string> the names of the default definitions that are stored per language
     */
    public array $translatableAttributes = [];

    protected function isTranslatableAttribute(string $name): bool
    {
        return in_array($name, $this->translatableAttributes, true);
    }
}
