<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/assets',
        __DIR__ . '/auth',
        __DIR__ . '/behaviors',
        __DIR__ . '/console',
        __DIR__ . '/controllers',
        __DIR__ . '/core',
        __DIR__ . '/data',
        __DIR__ . '/db',
        __DIR__ . '/filters',
        __DIR__ . '/gii',
        __DIR__ . '/helpers',
        __DIR__ . '/i18n',
        __DIR__ . '/mail',
        __DIR__ . '/messages',
        __DIR__ . '/models',
        __DIR__ . '/modules',
        __DIR__ . '/tasks',
        __DIR__ . '/tests',
        __DIR__ . '/validators',
        __DIR__ . '/views',
        __DIR__ . '/web',
        __DIR__ . '/widgets',
    ]);
    $rectorConfig->rules([
        InlineConstructorDefaultToPropertyRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictScalarReturnExprRector::class,
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81
    ]);
};
