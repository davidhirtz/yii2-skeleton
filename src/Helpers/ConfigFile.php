<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use Yii;
use yii\helpers\VarDumper;

/**
 * A file whose config and docblock are unchanged is left alone, so its `@version` stays the date of the last change.
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

        $contents = static::render($config, $phpdoc);

        if (is_file($file) && self::withoutVersion((string)file_get_contents($file)) === self::withoutVersion($contents)) {
            return true;
        }

        // A request reading the file while it is written would parse half of it, so it is swapped in whole.
        $temp = "$file." . SecretKey::generate(8) . '.tmp';

        if (@file_put_contents($temp, $contents) === false || !@rename($temp, $file)) {
            @unlink($temp);
            return false;
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }

        return true;
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

        // A line comes from outside (the acting username), and `*/` would end the comment and run the rest
        $lines = array_map(fn (string $line): string => str_replace('*/', '*\\/', $line), $lines);
        $phpdoc = $lines ? "\n * " . implode("\n * ", $lines) . "\n *" : '';

        return <<<EOL
<?php

declare(strict_types=1);

/**$phpdoc
 * @version $date
 */
return $export;
EOL;
    }

    private static function withoutVersion(string $contents): string
    {
        return (string)preg_replace('/^ \* @version .*$/m', '', $contents);
    }
}
