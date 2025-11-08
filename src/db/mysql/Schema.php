<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\db\mysql;

use davidhirtz\yii2\skeleton\db\Connection;
use davidhirtz\yii2\skeleton\db\Dsn;
use davidhirtz\yii2\skeleton\helpers\FileHelper;
use mikehaertl\shellcommand\Command;
use Yii;

/**
 * @property Connection $db
 */
class Schema extends \yii\db\mysql\Schema
{
    private ?string $tempConfigFile = null;

    public function getBackupCommand(): string
    {
        $baseCommand = (new Command('mysqldump'))
            ->addArg('--defaults-file=', $this->getTempConfigFile())
            ->addArg('--add-drop-table')
            ->addArg('--comments')
            ->addArg('--create-options')
            ->addArg('--dump-date')
            ->addArg('--no-autocommit')
            ->addArg('--routines')
            ->addArg('--default-character-set=', $this->db->charset)
            ->addArg('--set-charset')
            ->addArg('--triggers')
            ->addArg('--no-tablespaces')
            ->addArg('--set-gtid-purged=OFF');

        $schemaDump = (clone $baseCommand)
            ->addArg('--no-data')
            ->addArg('--skip-triggers')
            ->addArg('--result-file=', '{file}')
            ->addArg('{database}');

        $dataDump = (clone $baseCommand)
            ->addArg('--no-create-info');

        foreach ($this->db->ignoredBackupTables as $table) {
            $table = $this->getRawTableName($table);
            $dataDump->addArg('--ignore-table=', "{database}.$table");
        }

        $dataDump->addArg('{database}');

        return sprintf(
            '%s && %s >> "{file}"',
            $schemaDump->getExecCommand(),
            $dataDump->getExecCommand(),
        );
    }

    public function getRestoreCommand(): string
    {
        $command = (new Command('mysql'))
            ->addArg('--defaults-file=', $this->getTempConfigFile())
            ->addArg('{database}');

        return $command->getExecCommand() . ' < "{file}"';
    }

    public function getBackupFileExtension(): string
    {
        return 'sql';
    }

    protected function getTempConfigFile(): string
    {
        if ($this->tempConfigFile === null) {
            $contents = [
                '[client]',
                "user={$this->db->username}",
                'password="' . addslashes((string)$this->db->password) . '"',
            ];

            $dsn = Dsn::fromString($this->db->dsn);
            $contents[] = "host=$dsn->host";

            if ($dsn->port) {
                $contents[] = "port=$dsn->port";
            }

            $this->tempConfigFile = Yii::getAlias('@runtime/' . uniqid() . '.cnf');
            file_put_contents($this->tempConfigFile, implode(PHP_EOL, $contents) . PHP_EOL);
        }

        return $this->tempConfigFile;
    }

    public function __destruct()
    {
        if ($this->tempConfigFile) {
            FileHelper::unlink($this->tempConfigFile);
            $this->tempConfigFile = null;
        }
    }
}
