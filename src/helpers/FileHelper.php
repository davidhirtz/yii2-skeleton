<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\helpers;

use Yii;
use yii\helpers\BaseFileHelper;
use yii\helpers\VarDumper;

class FileHelper extends BaseFileHelper
{
    /**
     * This is a stream-wrapper-aware replacement to PHP's rename function. Renaming remote folders is not supported.
     */
    public static function rename(string $source, string $dest): bool
    {
        $source = Yii::getAlias($source);
        $dest = Yii::getAlias($dest);

        if (stream_is_local($source) == stream_is_local($dest)) {
            Yii::debug("Moving file \"$source\" to  \"$dest\"");
            return @rename($source, $dest);
        }

        if (is_dir($source)) {
            Yii::warning("Unable to rename directory \"$source\"");
            return false;
        }

        if (file_put_contents($dest, file_get_contents($source))) {
            Yii::debug("Moving remote file \"$source\" to  \"$dest\"");
            return static::unlink($source);
        }

        return false;
    }

    #[\Override]
    public static function unlink($path): bool
    {
        $path = Yii::getAlias($path);

        if (@parent::unlink($path) === false) {
            Yii::warning("Deleting file \"$path\" failed.");
            return false;
        }

        Yii::debug("Deleted file \"$path\"");

        return true;
    }

    #[\Override]
    public static function createDirectory($path, $mode = 0775, $recursive = true): bool
    {
        $path = Yii::getAlias($path);

        // Yii integration does not work with stream wrappers such as Amazon S3, if stream
        // is not local, let the implementation work out the specifics.
        if (!stream_is_local($path)) {
            return @mkdir($path);
        }

        return parent::createDirectory($path, $mode, $recursive);
    }

    #[\Override]
    public static function removeDirectory($dir, $options = []): void
    {
        $dir = Yii::getAlias($dir);
        parent::removeDirectory($dir, $options);
    }

    #[\Override]
    public static function findDirectories($dir, $options = []): array
    {
        $dir = Yii::getAlias($dir);
        return parent::findDirectories($dir, $options);
    }

    /**
     * Creates a config PHP file from the config array.
     */
    public static function createConfigFile(string $file, array $config = [], array|string|null $phpdoc = null): false|int
    {
        $file = Yii::getAlias($file);

        if (!static::createDirectory(dirname((string)$file))) {
            return false;
        }

        $export = VarDumper::export($config);
        $date = date('c');

        if ($phpdoc) {
            if (is_string($phpdoc)) {
                $phpdoc = preg_split("/\r\n|\n|\r/", $phpdoc);
            }

            $phpdoc = "\n * " . implode("\n * ", $phpdoc) . "\n *";
        }

        return file_put_contents(
            $file,
            <<<EOL
<?php
/**$phpdoc
 * @version $date
 */
return $export;
EOL
        );
    }

    public static function generateRandomFilename(string $path, string $extension, int $length = 8): string
    {
        $basename = $path . DIRECTORY_SEPARATOR . Yii::$app->getSecurity()->generateRandomString($length);
        return static::generateUniqueFilename($basename, $extension);
    }

    public static function generateUniqueFilename(string $basename, string $extension): string
    {
        $path = "$basename.$extension";
        $i = 0;

        while (file_exists($path)) {
            $path = $basename . '-' . ++$i . ".$extension";
        }

        return $path;
    }

    public static function getExtensionFromUrl(string $url): string
    {
        return strtolower(pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    public static function encodeUrl(string $url): string
    {
        return str_replace(['%2F', '%3A'], ['/', ':'], rawurlencode(rawurldecode($url)));
    }
}
