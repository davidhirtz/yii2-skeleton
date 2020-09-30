<?php

namespace davidhirtz\yii2\skeleton\helpers;

use Yii;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\helpers\BaseFileHelper;
use yii\helpers\VarDumper;

/**
 * Class FileHelper
 * @package davidhirtz\yii2\skeleton\helpers
 */
class FileHelper extends BaseFileHelper
{
    /**
     * @param string $dir
     * @param bool $includePath
     * @return array
     */
    public static function findDirectories($dir, $includePath = true)
    {
        $handle = @opendir($dir);
        $list = [];

        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $dir");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file[0] !== '.') {
                if (is_dir($path = $dir . DIRECTORY_SEPARATOR . $file)) {
                    $list[] = $includePath ? $path : $file;
                }
            }
        }

        closedir($handle);
        return $list;
    }

    /**
     * Removes a file, logging warnings.
     *
     * @param string $filename
     * @return bool
     */
    public static function removeFile($filename)
    {
        if (@unlink($filename) === false) {
            Yii::warning(strtr('Deleting file {name} failed.', ['{name}' => $filename]));
            return false;
        }

        return true;
    }

    /**
     * Moves file to new destination.
     *
     * If no extension is set the original extension will be used.
     * If no destination path is set, the source path will be used.
     *
     * @param string $source
     * @param string $dest
     * @param bool $throwException
     *
     * @return string the new file path.
     */
    public static function rename($source, $dest, $throwException = true)
    {
        if (!file_exists($source)) {
            $error = sprintf('Renaming %s to %s failed, source directory was not found.', $source, $dest);

            if ($throwException) {
                throw new Exception($error);
            }

            Yii::warning($error);
            return $source;
        }

        $path = pathinfo($dest);

        if (empty($path['dirname']) || $path['dirname'] == '.') {
            $path['dirname'] = dirname($source);
        } else {
            static::createDirectory($path['dirname']);
        }

        if (!is_dir($source)) {
            if (empty($path['extension'])) {
                $path['extension'] = pathinfo($source, PATHINFO_EXTENSION);
            }

            $dest = $path['dirname'] . DIRECTORY_SEPARATOR . $path['filename'] . '.' . $path['extension'];
        }

        if (@rename($source, $dest) === false) {
            $error = sprintf('Renaming %s to %s failed', $source, $dest);

            if ($throwException) {
                throw new Exception($error);
            }

            Yii::warning(sprintf('Renaming %s to %s failed', $source, $dest));
            return $source;
        }

        return $dest;
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