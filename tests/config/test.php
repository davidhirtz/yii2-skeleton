<?php

declare(strict_types=1);

return [
    'aliases' => [
        // This is a fix for the broken aliasing of `BaseMigrateController::getNamespacePath()`
        '@davidhirtz/yii2/skeleton' => __DIR__ . '/../../src/',
    ],
    'components' => [
//        'assetManager' => [
//            'basePath' => __DIR__ . '/../runtime/assets',
//        ],
        'db' => [
            'dsn' => getenv('MYSQL_DSN') ?: 'mysql:host=127.0.0.1;dbname=yii2_test',
            'username' => getenv('MYSQL_USER') ?: 'root',
            'password' => getenv('MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8',
        ],
    ],
    'params' => [
        'cookieValidationKey' => 'test',
    ],
];
