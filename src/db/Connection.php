<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\db;

use DateTime;
use davidhirtz\yii2\skeleton\db\mysql\Schema;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use davidhirtz\yii2\skeleton\models\Session;
use mikehaertl\shellcommand\Command;
use RuntimeException;
use Yii;

class Connection extends \yii\db\Connection
{
    public bool $backupOnMigration = true;
    public string $backupPath = '@runtime/backups';
    public ?array $ignoredBackupTables = null;
    public int|false $maxBackups = 10;
    private Schema $schema;

    public function init(): void
    {
        $this->ignoredBackupTables ??= [
            Session::tableName(),
        ];

        parent::init();
    }

    public function backup(): string|false
    {
        $file = $this->getBackupFilePath();

        if ($this->backupTo($file)) {
            $this->removeOutdatedBackups();
            return $file;
        }

        return false;
    }

    public function backupTo(string $filePath): bool
    {
        $command = $this->getSchema()->getBackupCommand();
        $command = $this->parseShellCommandTokens($command, $filePath);

        return $this->executeShellCommand($command);
    }

    public function restore(string $filename): bool
    {
        $command = $this->getSchema()->getRestoreCommand();
        $command = $this->parseShellCommandTokens($command, $filename);

        return $this->executeShellCommand($command);
    }

    protected function removeOutdatedBackups(): void
    {
        if ($this->maxBackups) {
            $files = $this->getBackups();

            if (count($files) >= $this->maxBackups) {
                $backupsToDelete = array_slice($files, $this->maxBackups);

                foreach ($backupsToDelete as $backupToDelete) {
                    FileHelper::unlink($backupToDelete);
                }
            }
        }
    }

    /**
     * @return string[]
     */
    public function getBackups(): array
    {
        $backupPath = Yii::getAlias($this->backupPath);
        $extension = '*.' . $this->getSchema()->getBackupFileExtension();
        $files = glob($backupPath . DIRECTORY_SEPARATOR . $extension) ?: [];

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $files;
    }

    protected function getBackupFilePath(): string
    {
        $path = Yii::getAlias($this->backupPath);

        if (!FileHelper::createDirectory($path)) {
            throw new RuntimeException("Could not create backup directory: $path");
        }

        $dsn = Dsn::fromString($this->dsn);
        $date = (new DateTime())->format('Y-m-d');
        $basename = $path . DIRECTORY_SEPARATOR . "$dsn->database-$date";
        $extension = $this->getSchema()->getBackupFileExtension();

        return FileHelper::generateUniqueFilename($basename, $extension);
    }

    protected function parseShellCommandTokens(string $command, string $file): string
    {
        // h/t https://stackoverflow.com/a/1250279/1688568
        $password = str_replace("'", "'\"'\"'", $this->password);
        $dsn = Dsn::fromString($this->dsn);

        $tokens = [
            '{file}' => $file,
            '{port}' => $dsn->port ?? '',
            '{server}' => $dsn->host,
            '{user}' => $this->username,
            '{password}' => $password,
            '{database}' => $dsn->database
        ];

        return str_replace(array_keys($tokens), $tokens, $command);
    }

    private function executeShellCommand(string $command): bool
    {
        $cmd = new Command();
        $cmd->setCommand($command);

        if (!function_exists('proc_open') && function_exists('exec')) {
            $cmd->useExec = true;
        }

        if (!$cmd->execute()) {
            Yii::error($cmd->getError(), __METHOD__);
            return false;
        }

        return true;
    }

    public function getSchema(): Schema
    {
        $this->schema ??= Yii::createObject([
            'class' => Schema::class,
            'db' => $this,
        ]);

        return $this->schema;
    }
}
