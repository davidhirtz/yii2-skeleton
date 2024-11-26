<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\helpers\StringHelper;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use Yii;

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
            $this->iconFilenameAttribute => Yii::t('skeleton', 'Icon'),
        ];
    }

    public static function getIconFilenames(): array
    {
        if (static::$_iconFilenames === null) {
            static::$_iconFilenames = [];

            foreach (static::findIconFiles() as $filename) {
                static::$_iconFilenames[basename((string) $filename)] = static::humanizeIconFilename($filename);
            }

            natcasesort(static::$_iconFilenames);
        }

        return static::$_iconFilenames;
    }

    protected static function findIconFiles(?array $options = null): array
    {
        $options ??= [
            'only' => ['*.svg']
        ];

        $dir = Yii::getAlias('@webroot') . static::getIconPath();

        return FileHelper::findFiles($dir, $options);
    }

    protected static function humanizeIconFilename(string $filename): string
    {
        return StringHelper::humanizeFilename($filename);
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
