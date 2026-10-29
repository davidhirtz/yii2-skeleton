<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

/**
 * Not {@see I18nAttributesTrait::$i18nAttributes}, which names columns: these values live in the JSON column under
 * their suffixed name.
 */
trait TranslatableAttributesTrait
{
    /**
     * @var list<string>
     */
    public array $translatableAttributes = [];

    protected function isTranslatableAttribute(string $name): bool
    {
        return in_array($name, $this->translatableAttributes, true);
    }
}
