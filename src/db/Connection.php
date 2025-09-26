<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\db;

use DateTime;
use davidhirtz\yii2\skeleton\db\mysql\Schema;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use Yii;

class Connection extends \yii\db\Connection
{
    public string $backupPath = '@runtime/backups';
    private Schema $schema;

    public function backup(): string
    {
        $file = $this->getBackupFilePath();
        $this->backupTo($file);
        return $file;
    }

    public function backupTo(string $filePath): void
    {
    }

    public function getBackupFilePath(): string
    {
        FileHelper::createDirectory($this->backupPath);

        $dsn = Dsn::fromString($this->dsn);
        $date = (new DateTime())->format('Y-m-d-His');
        $backupPath = Yii::getAlias($this->backupPath) . DIRECTORY_SEPARATOR;
        $filename = "$dsn->database-$date";
        $extension = '.sql';
        $path = $backupPath . $filename . $extension;
        $i = 0;

        while (file_exists($path)) {
            $path = $backupPath . $filename . '-' . ++$i . $extension;
        }

        return $path;
    }

    protected function parseCommandTokens(string $command, string $file): string
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

    public function getSchema(): Schema
    {
        $this->schema ??= Yii::createObject([
            'class' => Schema::class,
            'db' => $this,
        ]);

        return $this->schema;
    }
}
