<?php

use yii\web\Session;

return [
    'id' => 'yii2-skeleton',
    'aliases' => [
        // This is a fix for the broken aliasing of `BaseMigrateController::getNamespacePath()`
        '@davidhirtz/yii2/skeleton' => __DIR__ . '/../../src/',
    ],
    'components' => [
        'assetManager' => [
            'linkAssets' => true,
        ],
        'db' => [
            'dsn' => getenv('MYSQL_DSN') ?: ('mysql:host=127.0.0.1;dbname=' . (getenv('MYSQL_DATABASE') ?: 'yii2_skeleton_test')),
            'username' => getenv('MYSQL_USER') ?: 'root',
            'password' => getenv('MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8',
            ...is_file(__DIR__ . '/db.php') ? require(__DIR__ . '/db.php') : [],
        ],
        'mailer' => [
            'useFileTransport' => true,
        ],
        'session' => [
            'class' => Session::class,
        ],
        'urlManager' => [
            'showScriptName' => false,
        ],
    ],
    'params' => [
        'cookieValidationKey' => 'test',
    ],
];
