<?php

declare(strict_types=1);

/**
 * Shared configuration for the `message` command, spread by every bundle's own config.
 *
 * `sourcePath` is the whole `bundles` tree, not the bundle itself: a bundle routinely translates
 * through another bundle's category, and `removeUnused` would drop every key it cannot see.
 *
 * `keepMessages` names the keys no call site can reach at all: a permission's description lives in the
 * `auth_item.description` a migration seeds, as a `Message` pointer inside an SQL string the tokenizer never
 * reads, so `removeUnused` deleted it on every run (monorepo issue #211). Add one here beside the migration.
 */
return [
    'sourcePath' => dirname(__DIR__, 2),
    'messagePath' => __DIR__,
    'categories' => [
        'country',
        'skeleton',
    ],
    'keepMessages' => [
        'skeleton' => [
            'AUTH_AUTH_UPDATE_DESCRIPTION',
            'AUTH_REDIRECT_DESCRIPTION',
            'AUTH_SYSTEM_DESCRIPTION',
            'AUTH_TRAIL_INDEX_DESCRIPTION',
            'AUTH_USER_DESCRIPTION',
        ],
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
