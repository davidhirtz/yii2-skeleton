<?php

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

    /**
     * Creates a config PHP file from the config array.
     */
    public static function createConfigFile(string $file, array $config = [], array|string|null $phpdoc = null): false|int
    {
        $file = Yii::getAlias($file);

        if(!static::createDirectory(dirname($file))) {
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

    public static function generateRandomFilename(?string $extension = null, int $length = 8): string
    {
        $filename = Yii::$app->security->generateRandomString($length);

        if ($extension) {
            $filename .= '.' . (str_contains($extension, '.') ? strtolower(pathinfo($extension, PATHINFO_EXTENSION)) : $extension);
        }

        return $filename;
    }

    public static function getExtensionFromUrl(string $url): string
    {
        return strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    public static function encodeUrl(string $url): string
    {
        $parts = parse_url($url);

        // If the URL is relative, return it as is.
        if (!isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $path = array_map('rawurldecode', explode('/', $parts['path']));

        return "{$parts['scheme']}://{$parts['host']}" . implode('/', array_map('rawurlencode', $path));
    }
}
