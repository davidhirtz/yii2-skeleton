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
            ->addArg('--no-tablespaces');

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

    public function getBackupFileExtension(): string
    {
        return 'sql';
    }

    private function getTempConfigFile(): string
    {
        $contents = [
            '[client]',
            "user={$this->db->username}",
            'password="' . addslashes($this->db->password) . '"',
        ];

        $dsn = Dsn::fromString($this->db->dsn);
        $contents[] = "host=$dsn->host";

        if ($dsn->port) {
            $contents[] = "port=$dsn->port";
        }

        $path = Yii::getAlias('@runtime/' . uniqid() . '.cnf');
        file_put_contents($path, implode(PHP_EOL, $contents) . PHP_EOL);

        $this->db->on(Connection::EVENT_AFTER_BACKUP, fn () => FileHelper::unlink($path));

        return $path;
    }
}
