<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;
use yii\helpers\VarDumper;

/**
 * Writes a PHP file returning a config array, such as `config/params.php`. The top-level keys are sorted, so a file
 * saved by the admin or a console command reads the same whatever order it was edited in.
 */
class ConfigFile
{
    /**
     * @param array<string, mixed> $config
     * @param list<string>|string|null $phpdoc
     */
    public static function write(string $file, array $config = [], array|string|null $phpdoc = null): bool
    {
        $file = Yii::getAlias($file);

        if (!FileHelper::createDirectory(dirname($file))) {
            return false;
        }

        return file_put_contents($file, static::render($config, $phpdoc)) !== false;
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string>|string|null $phpdoc
     */
    public static function render(array $config = [], array|string|null $phpdoc = null): string
    {
        ksort($config, SORT_NATURAL | SORT_FLAG_CASE);

        $export = VarDumper::export($config);
        $date = date('c');

        $lines = is_string($phpdoc) ? preg_split("/\r\n|\n|\r/", $phpdoc) ?: [] : $phpdoc ?? [];
        $phpdoc = $lines ? "\n * " . implode("\n * ", $lines) . "\n *" : '';

        return <<<EOL
<?php
/**$phpdoc
 * @version $date
 */
return $export;
EOL;
    }
}
