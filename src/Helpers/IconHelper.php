<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;

class IconHelper
{
    /**
     * @var array<string, array<string, string>>
     */
    private static array $filenames = [];

    /**
     * @return array<string, string> the humanized name per filename
     */
    public static function getIconFilenames(string $path, ?array $options = null): array
    {
        $key = $path . serialize($options);

        if (!isset(self::$filenames[$key])) {
            $filenames = [];

            foreach (FileHelper::findFiles(Yii::getAlias('@webroot') . $path, $options ?? ['only' => ['*.svg']]) as $filename) {
                $filenames[basename((string)$filename)] = StringHelper::humanizeFilename($filename);
            }

            natcasesort($filenames);
            self::$filenames[$key] = $filenames;
        }

        return self::$filenames[$key];
    }
}
