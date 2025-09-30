<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\console\controllers\traits;

use Yii;
use yii\helpers\Console;

trait BackupTrait
{
    use ControllerTrait;

    public function actionBackup(): void
    {
        $this->interactiveStartStdout('Backing up database...');
        $this->interactiveDoneStdout(Yii::$app->getDb()->backup() !== false);
    }

    public function actionRestore(): void
    {
        $backups = Yii::$app->getDb()->getBackups();

        if (!$backups) {
            $this->stdout('No database backups found.' . PHP_EOL, Console::FG_YELLOW);
            return;
        }

        $this->stdout('Available backups:' . PHP_EOL, Console::FG_YELLOW);

        foreach ($backups as $i => $file) {
            $this->stdout(sprintf(' [%d] %s', $i + 1, basename($file)) . PHP_EOL, Console::FG_YELLOW);
        }

        $index = $this->prompt('Select backup to restore (number): ', [
            'required' => true,
            'pattern' => '/^[1-9][0-9]*$/',
            'error' => 'Please enter a valid number.',
        ]);

        $index = (int)$index - 1;

        if (!isset($backups[$index])) {
            $this->stdout('Invalid selection.' . PHP_EOL, Console::FG_RED);
            return;
        }

        $filename = $backups[$index];
    }
}
