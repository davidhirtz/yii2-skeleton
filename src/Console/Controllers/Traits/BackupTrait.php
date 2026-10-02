<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers\Traits;

use Yii;
use yii\console\ExitCode;
use yii\helpers\Console;

trait BackupTrait
{
    use ControllerTrait;

    /**
     * Backs up the database.
     */
    public function actionBackup(): int
    {
        $this->interactiveStartStdout('Backing up database ...');

        $isBackedUp = Yii::$app->getDb()->backup() !== false;
        $this->interactiveDoneStdout($isBackedUp);

        if (!$isBackedUp) {
            $this->stderr('The database backup failed.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Restores the database from a backup.
     */
    public function actionRestore(): int
    {
        $backups = Yii::$app->getDb()->getBackups();

        if (!$backups) {
            $this->stderr('No database backups found.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('Available backups:' . PHP_EOL);

        foreach ($backups as $i => $file) {
            $this->stdout(sprintf(' [%d] %s', $i + 1, basename((string) $file)) . PHP_EOL);
        }

        $index = $this->prompt('Select backup to restore (number):', [
            'required' => true,
            'pattern' => '/^[1-9][0-9]*$/',
            'error' => 'Please enter a valid number.',
        ]);

        $index = (int)$index - 1;

        if (!isset($backups[$index])) {
            $this->stderr('Invalid selection.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $filename = $backups[$index];

        $this->interactiveStartStdout('Restoring database from backup ...');

        $isRestored = Yii::$app->getDb()->restore($filename) !== false;
        $this->interactiveDoneStdout($isRestored);

        if (!$isRestored) {
            $this->stderr('The database restore failed.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
