<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use Yii;
use yii\data\ArrayDataProvider;

/**
 * Class LogDataProvider
 * @package davidhirtz\yii2\skeleton\modules\admin\data
 */
class LogDataProvider extends ArrayDataProvider
{
    /**
     * @var string the file path of the current error log
     */
    public $file;

    /**
     * @var string the regex pattern to explode error logs into single entries
     */
    public $pattern = '/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]/';

    /**
     * @var string the path used for parsing error files
     */
    public $basePath = '@app/runtime/logs/';

    /**
     * @inheritDoc
     */
    public function init()
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

    /**
     * @return array
     */
    protected function parseFile()
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

        return $models;
    }

    /**
     * @return array
     */
    protected function findFiles()
    {
        $files = [];

        foreach (glob(Yii::getAlias($this->basePath . '*')) as $file) {
            $files[pathinfo($file, PATHINFO_BASENAME)] = filemtime($file);
        }

        return $files;
    }

    /**
     * @return bool
     */
    public function isFileValid()
    {
        return is_file($this->file);
    }
}