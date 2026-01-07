<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\I18n;

use Hirtz\Skeleton\Modules\ModuleTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\Module;

class I18nTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$app->language = 'en-US';
    }

    public function testTranslatedTableNames(): void
    {
        $name = Yii::$app->getI18n()->getTableName('test', 'de');
        self::assertEquals('{{%test_de}}', $name);
    }

    public function testModuleWithTranslatedTables(): void
    {
        Yii::$app->setModule('test', [
            'class' => TestModule::class,
        ]);

        /** @var TestModule $module */
        $module = Yii::$app->getModule('test');
        Yii::$app->language = 'de';

        self::assertEquals('{{%prefix_test_de}}', $module->getTableName('test'));
        self::assertEquals('app\\Models\\Test::de', $module->getI18nClassName('app\\Models\\Test'));
    }
}

class TestModule extends Module
{
    use ModuleTrait;

    #[Override]
    public function init(): void
    {
        $this->enableI18nTables = true;
        $this->tablePrefix = 'prefix_';

        parent::init();
    }
}
