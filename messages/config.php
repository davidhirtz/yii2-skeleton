<?php

/**
 * This is the configuration for generating message translations
 * for the Yii framework. It is used by the 'yii message' command.
 */
return [
    'sourcePath' => dirname(dirname(__DIR__)),
    'messagePath' => __DIR__,
    'languages' => ['de', 'en-US', 'zh-CN', 'zh-TW'],
    'ignoreCategories' => [
        'anakin',
        'cms',
        'config',
        'hotspot',
        'media',
        'newsletter',
        'shopify',
        'yii',
    ],
    'overwrite' => true,
    'removeUnused' => true,
    'only' => ['*.php'],
    'format' => 'php',
    'sort' => true,
    'except' => [
        '/config',
        '/messages',
        '/tests',
    ],
];

