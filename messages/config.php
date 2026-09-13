<?php

declare(strict_types=1);

/**
 * This is the configuration for generating message translations
 * for the Yii framework. It is used by the 'yii message' command.
 */
return [
    'sourcePath' => dirname(__DIR__, 1),
    'messagePath' => __DIR__,
    'languages' => [
        'de',
        'en-US',
        'fr',
        'pt',
        'ru',
        'zh-CN',
        'zh-TW',
    ],
    // `Message::make()` stores a pointer instead of rendered text, so its keys live nowhere else
    'translator' => ['Yii::t', '\\Yii::t', 'Message::make'],
    'ignoreCategories' => ['yii'],
    'overwrite' => true,
    'removeUnused' => true,
    'only' => ['*.php'],
    'format' => 'php',
    'sort' => true,
    'except' => [
        'messages',
    ],
];
