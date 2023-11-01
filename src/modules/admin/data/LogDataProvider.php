<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use Yii;
use yii\data\ArrayDataProvider;

class LogDataProvider extends ArrayDataProvider
{
    /**
     * @var string|null the file path of the current error log
     */
    public ?string $file = null;

    /**
     * @var string the regex pattern to explode error logs into single entries
     */
    public string $pattern = '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]/';

    /**
     * @var string the path used for parsing error files
     */
    public string $basePath = '@app/runtime/logs/';

    public function init(): void
    {
        if ($this->allModels === null) {
            if ($this->file) {
                $this->file = Yii::getAlias($this->basePath . $this->file);
            }

            $this->allModels = $this->file ? $this->parseFile() : $this->findFiles();
            $this->setPagination(false);
        }

        parent::init();
    }

    protected function parseFile(): array
    {
        $models = [];
        $log = [];

        if ($file = @fopen($this->file, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);
                preg_match($this->pattern, $line, $logInfo);

                if (count($logInfo) === 7) {
                    if (!empty($log)) {
                        $models[] = $log;
                    }

                    $log = [
                        'date' => $logInfo[1],
                        'message' => substr($line, strlen($logInfo[0]) + 1),
                        'ip' => $logInfo[2],
                        'user_id' => $logInfo[3],
                        'session_id' => $logInfo[4],
                        'level' => $logInfo[5],
                        'category' => $logInfo[6],
                        'vars' => ''
                    ];
                } elseif (isset($log['vars'])) {
                    $log['vars'] .= $line;
                }
            }

            if (!empty($log)) {
                $models[] = $log;
            }

            fclose($file);
        }

        return array_reverse($models);
    }

    protected function findFiles(): array
    {
        $files = [];

        foreach (glob(Yii::getAlias($this->basePath . '*')) as $file) {
            $files[pathinfo($file, PATHINFO_BASENAME)] = filemtime($file);
        }

        return $files;
    }

    public function isFileValid(): bool
    {
        return is_file($this->file);
    }
}