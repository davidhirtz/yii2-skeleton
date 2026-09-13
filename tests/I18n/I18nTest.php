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

    public function testTheSessionLanguageOutlivesOnlyItsConfiguration(): void
    {
        $i18n = Yii::$app->getI18n();
        $i18n->setSessionLanguage('de');

        self::assertSame('de', $i18n->getSessionLanguage());

        $i18n->setLanguages(['en-US']);
        self::assertNull($i18n->getSessionLanguage());

        $i18n->setLanguages(['en-US', 'de']);
        $i18n->setSessionLanguage(null);

        self::assertNull($i18n->getSessionLanguage());
    }
}
