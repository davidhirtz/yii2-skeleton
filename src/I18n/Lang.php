<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\I18n;

use Yii;

/**
 * Facade for message translation. Replaces direct `Yii::t()` calls throughout the platform.
 *
 * Messages are addressed by uppercase, domain-first keys (e.g. `ENTRY_NAME_LABEL`,
 * `ENTRY_FLASH_ASSET_ORDER_CHANGED`) resolved against a `forceTranslation` message source, so the
 * source language is looked up in the message files just like every other language. See UPGRADE.md.
 */
final class Lang
{
    /**
     * @param array<string, mixed> $params
     */
    public static function t(string $category, string $key, array $params = [], ?string $language = null): string
    {
        return (string)Yii::t($category, $key, $params, $language);
    }
}
