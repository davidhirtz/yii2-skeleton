<?php

declare(strict_types=1);

/**
 * Shared configuration for the `message` command, spread by every bundle's own config.
 *
 * `sourcePath` is the whole `bundles` tree, not the bundle itself: a bundle routinely translates
 * through another bundle's category, and `removeUnused` would drop every key it cannot see.
 */
return [
    'sourcePath' => dirname(__DIR__, 2),
    'messagePath' => __DIR__,
    'categories' => [
        'country',
        'skeleton',
    ],
    'languages' => [
        'de',
        'en-US',
        'fr',
        'pt',
    ],
    'translator' => [
        'Yii::t',
        '\\Yii::t',
        'Message::make',
    ],
    'ignoreCategories' => ['yii'],
    'overwrite' => true,
    'removeUnused' => true,
    'only' => ['*.php'],
    'except' => [
        'messages',
        'node_modules',
        'tests',
        'vendor',
    ],
    'format' => 'php',
    'sort' => true,
    'phpDocBlock' => <<<'DOCBLOCK'
        /**
         * Message translations (domain-first keys; forceTranslation). See UPGRADE.md.
         *
         * NOTE: this file must be saved in UTF-8 encoding.
         */
        DOCBLOCK,
];
