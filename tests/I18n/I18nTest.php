<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\I18n;

use Hirtz\Skeleton\Test\TestCase;
use Yii;

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

    public function testLabels(): void
    {
        $i18n = Yii::$app->getI18n();

        self::assertSame('Deutsch', $i18n->getLabel('de'));
        self::assertSame('nl', $i18n->getLabel('nl'));
    }

    public function testLanguageCodes(): void
    {
        $i18n = Yii::$app->getI18n();

        self::assertSame('en', $i18n->getLanguageCode());
        self::assertSame('de', $i18n->getLanguageCode('de'));
        self::assertSame('en', $i18n->getLanguageCode('en-US'));
    }
}
