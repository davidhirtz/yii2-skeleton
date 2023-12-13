<?php

return [
    'id' => 'yii2-skeleton',
    'aliases' => [
        // This is a fix for the broken aliasing of `BaseMigrateController::getNamespacePath()`
        '@davidhirtz/yii2/skeleton' => __DIR__ . '/../../src/',
    ],
    'components' => [
        'db' => require(__DIR__ . '/db.php'),
        'mailer' => [
            'useFileTransport' => true,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
        ],
    ],
];