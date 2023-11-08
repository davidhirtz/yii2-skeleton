<?php

namespace davidhirtz\yii2\skeleton\models\traits;

use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\helpers\StringHelper;
use Yii;

trait IconFilenameAttributeTrait
{
    private static ?array $_iconFilenames = null;

    protected string $iconFilenameAttribute = 'icon_filename';

    public static function getIconFilenames(): array
    {
        if (static::$_iconFilenames === null) {
            $path = Yii::getAlias('@webroot') . '/' . static::getIconPath();
            static::$_iconFilenames = [];

            foreach (static::findIconFiles() as $filename) {
                $filename = str_replace($path, '', $filename);
                static::$_iconFilenames[$filename] = static::humanizeIconFilename($filename);
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

        return FileHelper::findFiles(static::getIconPath(), $options);
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