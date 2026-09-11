<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Helpers\IconHelper;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;

trait IconFilenameAttributeTrait
{
    public string $iconFilenameAttribute = 'icon_filename';
    private static ?array $_iconFilenames = null;

    /**
     * @uses static::getIconFilenames()
     */
    public function getIconFilenameAttributeTraitRules(): array
    {
        return [
            [
                $this->iconFilenameAttribute,
                DynamicRangeValidator::class,
                'integerOnly' => false,
            ],
        ];
    }

    public function getIconFilenameAttributeTraitAttributeLabels(): array
    {
        return [
            $this->iconFilenameAttribute => Lang::t('skeleton', 'ICON_FILENAME_ATTRIBUTE_ICON'),
        ];
    }

    public static function getIconFilenames(): array
    {
        return static::$_iconFilenames ??= IconHelper::getIconFilenames(static::getIconPath());
    }

    public function getIcon(): string
    {
        return $this->{$this->iconFilenameAttribute}
            ? (static::getIconPath() . $this->{$this->iconFilenameAttribute})
            : '';
    }

    public static function getIconPath(): string
    {
        return '/images/icons/';
    }
}
