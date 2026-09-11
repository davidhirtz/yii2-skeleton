<?php

declare(strict_types=1);

use Hirtz\Skeleton\Db\Dsn;

$dsn = getenv('MYSQL_DSN') ?: 'mysql:host=127.0.0.1;dbname=yii2_test';

// paratest numbers its workers, and each one runs against its own copy of the database
if ($token = getenv('TEST_TOKEN')) {
    $dsn = Dsn::fromString($dsn);
    $dsn = (string)new Dsn($dsn->driver, $dsn->host, "{$dsn->database}_$token", $dsn->port, $dsn->charset);
}

return [
    'components' => [
        'db' => [
            'dsn' => $dsn,
            'username' => getenv('MYSQL_USER') ?: 'root',
            'password' => getenv('MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8',
        ],
    ],
];
