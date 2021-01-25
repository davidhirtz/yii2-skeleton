<?php

namespace davidhirtz\yii2\skeleton\helpers;

use Yii;
use yii\helpers\BaseFileHelper;
use yii\helpers\VarDumper;

/**
 * Class FileHelper
 * @package davidhirtz\yii2\skeleton\helpers
 */
class FileHelper extends BaseFileHelper
{
    /**
     * Removes a file, logging warnings.
     *
     * @param string $filename
     * @return bool
     */
    public static function removeFile($filename)
    {
        if (@unlink($filename) === false) {
            Yii::warning("Deleting file \"{$filename}\" failed.");
            return false;
        }

        return true;
    }

    /**
     * This is a stream wrapper aware replacement to PHP's rename function. Renaming
     * remote folders is not supported.
     *
     * @param string $source
     * @param string $dest
     *
     * @return string the new file path.
     */
    public static function rename($source, $dest)
    {
        if (stream_is_local($source) == stream_is_local($dest)) {
            Yii::debug("Moving file \"{$source}\" to  \"{$dest}\"");
            return @rename($source, $dest);
        }

        if (is_dir($source)) {
            Yii::warning("Unable to rename directory \"{$source}\"");
            return false;
        }

        if (file_put_contents($dest, file_get_contents($source))) {
            Yii::debug("Moving remote file \"{$source}\" to  \"{$dest}\"");
            return @unlink($source);
        }

        return false;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function unlink($path)
    {
        Yii::debug("Deleting file \"{$path}\"");
        return @parent::unlink($path);
    }

    /**
     * @inheritDoc
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        // Yii integration does not work with stream wrappers such as Amazon S3, if stream
        // is not local, let the implementation work out the specifics.
        if (!stream_is_local($path)) {
            return @mkdir($path);
        }

        return parent::createDirectory($path, $mode, $recursive);
    }

    /**
     * Creates a config PHP file from config array.
     *
     * @param string $file
     * @param array $config
     * @param array|string|null $phpdoc
     */
    public static function createConfigFile($file, $config = [], $phpdoc = null)
    {
        $file = Yii::getAlias($file);
        $export = VarDumper::export($config);
        $date = date('c');

        if ($phpdoc) {
            if (is_string($phpdoc)) {
                $phpdoc = preg_split("/\r\n|\n|\r/", $phpdoc);
            }

            $phpdoc = "\n * " . implode("\n * ", $phpdoc) . "\n *";
        }

        file_put_contents($file, <<<EOL
<?php
/**$phpdoc
 * @version $date
 */
return $export;
EOL
        );
    }

    /**
     * @param string|null $extension
     * @param int $length
     *
     * @return string
     */
    public static function generateRandomFilename($extension = null, $length = 8)
    {
        $filename = Yii::$app->security->generateRandomString($length);

        if ($extension) {
            $filename .= '.' . (strpos($extension, '.') !== false ? strtolower(pathinfo($extension, PATHINFO_EXTENSION)) : $extension);
        }

        return $filename;
    }

    /**
     * @param string $url
     * @return string
     */
    public static function getExtensionFromUrl($url)
    {
        return strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }
}