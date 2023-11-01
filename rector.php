<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
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

    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\CounterColumn' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\CounterColumn',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\MessageSourceTrait' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\MessageSourceTrait',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\StatusGridViewTrait' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\StatusGridViewTrait',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\GridView',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\LogGridView' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\LogGridView',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\RedirectGridView' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\RedirectGridView',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TrailGridView' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\TrailGridView',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserGridView' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserGridView',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserLoginGridView' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserLoginGridView',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserLoginGridView' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\navs\NavBar',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\nav\TrailSubmenu' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\navs\TrailSubmenu',
        'davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu' => 'davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu',
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
