<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use davidhirtz\yii2\skeleton\models\Log;
use Override;
use Yii;
use yii\data\ArrayDataProvider;

/**
 * @property Log[]|null $allModels
 */
class LogDataProvider extends ArrayDataProvider
{
    public string $basePath = '@runtime/logs/';
    public string $file;

    /**
     * @var string the regex pattern to explode error logs into single entries
     */
//    public string $pattern = '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)](.+?)(?=\n| \$_GET|$)/';
    public string $pattern = '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]/';


    #[Override]
    public function init(): void
    {
        $this->file = Yii::getAlias($this->basePath . $this->file);
        $this->allModels ??= $this->parseFile();

        $this->setPagination(false);

        parent::init();
    }

    protected function parseFile(): array
    {
        $models = [];
        $current = null;

        if ($file = @fopen($this->file, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);

                if (false === $line) {
                    continue;
                }

                preg_match($this->pattern, $line, $data);

                if (count($data) > 6) {
                    $next = Log::createFromData($line, $data);

                    // Append info messages to the previous log entry if they share the same date, these only contain
                    // the request parameters.
                    if ('info' === $next->level && $current?->date === $next->date) {
                        $current->content .= "\n$next->message\n";
                        continue;
                    }

                    if (null !== $current) {
                        $models[] = $current;
                    }

                    $current = $next;
                    continue;
                }

                if ($current) {
                    $current->content .= $line;
                }
            }

            if (null !== $current) {
                $models[] = $current;
            }

            fclose($file);
        }

        return array_reverse($models);
    }

    protected function findFiles(): array
    {
        $files = [];

        foreach (glob(Yii::getAlias($this->basePath . '*')) as $file) {
            $files[] = [
                'filename' => pathinfo($file, PATHINFO_BASENAME),
                'updated_at' => filemtime($file),
            ];
        }

        return $files;
    }

    public function isFileValid(): bool
    {
        return is_file($this->file);
    }
}
