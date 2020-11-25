<?php

namespace davidhirtz\yii2\skeleton\modules\admin\data;

use yii\data\ArrayDataProvider;

/**
 * Class LogDataProvider
 * @package davidhirtz\yii2\skeleton\modules\admin\data
 */
class LogDataProvider extends ArrayDataProvider
{
    /**
     * @var string
     */
    public $file;

    /**
     * @var array
     */
    public $items;

    /**
     * @inheritDoc
     */
    public function init()
    {
        if ($this->file) {
            $this->parseFile($this->file);
        }

        parent::init();
    }

    /**
     * @param string $file
     * @return bool
     */
    protected function parseFile($file)
    {
        $pattern = "/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]\[([^]]*)]/";
        $models = [];
        $log = [];

        if ($file = fopen($file, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);
                preg_match($pattern, $line, $logInfo);

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
                } elseif(isset($log['vars'])) {
                    $log['vars'] .= $line;
                }
            }

            if (!empty($log)) {
                $models[] = $log;
            }

            fclose($file);
            $this->setModels(array_reverse($models));

            return true;
        }


        return false;
    }
}