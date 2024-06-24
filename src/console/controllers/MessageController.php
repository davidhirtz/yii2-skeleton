<?php

namespace davidhirtz\yii2\skeleton\console\controllers;

use davidhirtz\yii2\skeleton\console\controllers\traits\ControllerTrait;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\VarDumper;

class MessageController extends \yii\console\controllers\MessageController
{
    use ControllerTrait;

    public function actionExtract($configFile = null): void
    {
        $configFile ??= $this->getDefaultConfigPath();
        parent::actionExtract($configFile);
    }

    /**
     * Exports translations to CSV format after running extract.
     */
    public function actionExportCsv(?string $destination = null, ?string $configFile = null): void
    {
        if ($this->format !== 'php') {
            throw new Exception('Only PHP format is supported.');
        }

        $this->actionExtract($configFile);

        $destination = Yii::getAlias($destination ?? '@runtime/messages');
        FileHelper::createDirectory($destination);

        $this->interactiveStartStdout("Exporting translations ...");

        $files = FileHelper::findFiles($this->config['messagePath'], [
            'only' => ['*/*.php'],
            'recursive' => true,
        ]);

        $messages = [];

        foreach ($files as $file) {
            $category = pathinfo((string) $file, PATHINFO_FILENAME);
            $language = pathinfo(dirname((string) $file), PATHINFO_FILENAME);
            $messages[$category][$language] = require $file;
        }

        foreach ($messages as $category => $languages) {
            $filename = "$destination/$category.csv";
            $fp = fopen($filename, 'w');

            fputcsv($fp, array_unique([
                Yii::$app->sourceLanguage,
                ...array_keys($languages),
            ]));

            foreach ($languages[Yii::$app->sourceLanguage] as $source => $translation) {
                $row = [$source];

                foreach ($languages as $language => $translations) {
                    if ($language !== Yii::$app->sourceLanguage) {
                        $row[] = $translations[$source] ?? '';
                    }
                }

                fputcsv($fp, $row);
            }

            fclose($fp);
        }

        $this->interactiveDoneStdout();
    }

    /**
     * Imports translations from CSV file.
     */
    public function actionImportCsv(?string $source = null, ?string $configFile = null): int
    {
        if ($this->format !== 'php') {
            throw new Exception('Only PHP format is supported.');
        }

        if (!$source) {
            throw new Exception('Source file in CSV format must be provided.');
        }

        $source = Yii::getAlias($source);

        if (!is_file($source)) {
            throw new Exception("Failed to read source file \"$source\".");
        }

        $file = fopen($source, 'r');
        $languages = fgetcsv($file);

        if (($languages[0] ?? null) !== Yii::$app->sourceLanguage) {
            throw new Exception('Source file must contain source language as first column.');
        }

        $configFile ??= $this->getDefaultConfigPath();
        $this->initConfig($configFile);

        foreach ($languages as $language) {
            if (!in_array($language, $this->config['languages'])) {
                throw new Exception("Language \"$language\" is not supported.");
            }
        }

        $this->interactiveStartStdout("Importing translations ...");

        $category = pathinfo($source, PATHINFO_FILENAME);
        $messages = [];

        while (($row = fgetcsv($file)) !== false) {
            foreach ($row as $key => $value) {
                if ($key === 0) {
                    $source = trim((string) $value);
                    $messages[Yii::$app->sourceLanguage][$source] = '';
                } else {
                    $messages[$languages[$key]][$source] = trim((string) $value);
                }
            }
        }

        fclose($file);

        foreach ($messages as $language => $translations) {
            $filename = "{$this->config['messagePath']}/$language/$category.php";

            if (file_exists($filename)) {
                $existing = require $filename;
                $translations = [...$existing, ...$translations];
            }

            if ($this->config['sort']) {
                ksort($translations);
            }

            $array = VarDumper::export($translations);
            $content = <<<EOD
<?php
{$this->config['phpFileHeader']}{$this->config['phpDocBlock']}
return $array;

EOD;

            FileHelper::createDirectory(dirname($filename));

            if (file_put_contents($filename, $content, LOCK_EX) === false) {
                $this->interactiveDoneStdout(false);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $this->interactiveDoneStdout();
        return ExitCode::OK;
    }

    protected function getDefaultConfigPath(): ?string
    {
        $file = Yii::getAlias('@messages/config.php');
        return is_file($file) ? $file : null;
    }
}
