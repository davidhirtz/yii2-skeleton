<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\i18n;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\modules\ModuleTrait;
use Yii;
use yii\base\Module;

class I18nTest extends Unit
{
    protected function _before(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$app->language = 'en-US';

        parent::_before();
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
        self::assertEquals('app\\models\\Test::de', $module->getI18nClassName('app\\models\\Test'));
    }
}

class TestModule extends Module
{
    use ModuleTrait;

    #[\Override]
    public function init(): void
    {
        $this->enableI18nTables = true;
        $this->tablePrefix = 'prefix_';

        parent::init();
    }
}
