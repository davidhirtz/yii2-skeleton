<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\db\mysql;

use davidhirtz\yii2\skeleton\db\Dsn;
use davidhirtz\yii2\skeleton\helpers\FileHelper;

class Schema extends \yii\db\mysql\Schema
{
    protected function createDumpConfigFile(): string
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

        $path = FileHelper::normalizePath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . uniqid() . '.cnf';
        file_put_contents($path, implode(PHP_EOL, $contents) . PHP_EOL);

        return $path;
    }
}
