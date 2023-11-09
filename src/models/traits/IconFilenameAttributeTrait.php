<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\helpers\StringHelper;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use Yii;

trait IconFilenameAttributeTrait
{
    private static ?array $_iconFilenames = null;

    protected string $iconFilenameAttribute = 'icon_filename';

    /**
     * @uses static::getIconFilenames()
     */
    public function getIconFilenameAttributeTraitRules(): array
    {
        return [
            [
                [$this->iconFilenameAttribute],
                DynamicRangeValidator::class,
            ]
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
                static::$_iconFilenames[basename($filename)] = static::humanizeIconFilename($filename);
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

        return FileHelper::findFiles(Yii::getAlias(static::getIconPath()), $options);
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
        return '@web/images/icons/';
    }
}
