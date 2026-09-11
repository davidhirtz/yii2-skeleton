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
        $dir = Yii::getAlias('@webroot') . $path;
        $key = $dir . serialize($options);

        // A project without an icon directory has no icons, rather than a failing lookup. The result is not cached,
        // so a directory created later is still picked up.
        if (!is_dir($dir)) {
            return [];
        }

        if (!isset(self::$filenames[$key])) {
            $filenames = [];

            foreach (FileHelper::findFiles($dir, $options ?? ['only' => ['*.svg']]) as $filename) {
                $filenames[basename((string)$filename)] = StringHelper::humanizeFilename($filename);
            }

            natcasesort($filenames);
            self::$filenames[$key] = $filenames;
        }

        return self::$filenames[$key];
    }
}
