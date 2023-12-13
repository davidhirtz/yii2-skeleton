<?php

use yii\debug\Module;

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
        'db' => require(__DIR__ . '/db.php'),
        'mailer' => [
            'useFileTransport' => true,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
        ],
        'urlManager' => [
            'showScriptName' => false,
        ],
    ],
];