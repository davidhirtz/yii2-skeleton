<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Collections;

use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Yii;

class TrailModelCollectionTest extends TestCase
{
    /**
     * The declaration is indexed by offset, so a type whose value is not its own offset was never found.
     */
    public function testATypeIsFormattedWithItsDefinitionName(): void
    {
        $value = TrailModelCollection::formatAttributeValue(Redirect::create(), 'type', Redirect::TYPE_FOUND);
        self::assertSame('302 - Temporary redirect', $value);
    }

    /**
     * A declaration offset that does hold a definition used to reach the object and fatal on `$value['name']`.
     */
    public function testATypeWhoseValueCollidesWithAnotherDeclarationOffset(): void
    {
        $value = TrailModelCollection::formatAttributeValue(Trail::create(), 'type', Trail::TYPE_UPDATE);
        self::assertSame('Updated', $value);
    }

    public function testATypeIsFormattedInTheReadersLanguage(): void
    {
        Yii::$app->getI18n()->setLanguages(['en-US', 'de']);
        Yii::$app->language = 'de';

        $value = TrailModelCollection::formatAttributeValue(Trail::create(), 'type', Trail::TYPE_UPDATE);
        self::assertSame('Aktualisiert', $value);
    }

    public function testAnUnknownTypeFallsBackToItsValue(): void
    {
        $value = TrailModelCollection::formatAttributeValue(Redirect::create(), 'type', 418);
        self::assertSame(418, $value);
    }

    /**
     * A range attribute whose model answers a plain `value => label` map rather than definitions.
     */
    public function testAPlainLabelMapIsStillFormatted(): void
    {
        $value = TrailModelCollection::formatAttributeValue(User::create(), 'language', 'en-US');
        self::assertSame('English', $value);
    }
}
