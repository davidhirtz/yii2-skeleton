<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\UrlCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

/**
 * The rule and `<input type="url">` have to agree: whatever the browser lets through must validate, and whatever
 * it refuses must not be quietly accepted somewhere else.
 */
class UrlCustomAttributeTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(UrlRecord::tableName(), [
                'id' => 'pk',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(UrlRecord::tableName())
            ->execute();
    }

    public function testAnInternationalizedHostIsAcceptedLikeAnInternationalizedPath(): void
    {
        self::assertTrue($this->validate('https://münchen.de'));
        self::assertTrue($this->validate('https://münchen.de/über-uns'));
        self::assertTrue($this->validate('https://example.com/über-uns'));
    }

    /**
     * A scheme-less value is what `defaultScheme` used to rewrite, which the browser never let through.
     */
    public function testAValueTheBrowserRefusesIsRefusedHereToo(): void
    {
        self::assertFalse($this->validate('example.com'));
        self::assertFalse($this->validate('münchen.de'));
        self::assertFalse($this->validate('not a url'));
    }

    public function testAPhoneNumberIsAccepted(): void
    {
        self::assertTrue($this->validate('tel:+4930123456'));
        self::assertTrue($this->validate('tel:+49 (30) 123-456'));
        self::assertTrue($this->validate('TEL:030/123.456'));
        self::assertTrue($this->validate('tel:+4930123456', RelativeUrlRecord::class));

        self::assertFalse($this->validate('tel:'));
        self::assertFalse($this->validate('tel:call me'));
        self::assertFalse($this->validate('tel:+49 30 123 ✆'));
    }

    public function testAnEmailAddressIsAccepted(): void
    {
        self::assertTrue($this->validate('mailto:info@example.com'));
        self::assertTrue($this->validate('mailto:müller@münchen.de?subject=Hallo%20Welt'));
        self::assertTrue($this->validate('MAILTO:info@example.com'));

        self::assertFalse($this->validate('mailto:'));
        self::assertFalse($this->validate('mailto:info'));
        self::assertFalse($this->validate('mailto:info @example.com'));
    }

    public function testOnlyTheConfiguredSchemesAreAccepted(): void
    {
        self::assertFalse($this->validate('sms:+4930123456'));
        self::assertFalse($this->validate('ftp://example.com'));
        self::assertFalse($this->validate('javascript:alert(1)'));

        self::assertTrue($this->validate('sms:+4930123456?body=Hallo', SchemesUrlRecord::class));
        self::assertTrue($this->validate('ftp://example.com/file.zip', SchemesUrlRecord::class));
        self::assertFalse($this->validate('https://example.com', SchemesUrlRecord::class));
        self::assertFalse($this->validate('tel:+4930123456', SchemesUrlRecord::class));
    }

    public function testTheFieldIsAUrlInput(): void
    {
        $record = UrlRecord::create();
        $field = $record->getCustomAttributeDefinitions()['link']->createField($record);

        self::assertStringContainsString('type="url"', (string)$field->render());
    }

    public function testARelativeUrlAcceptsAPathAQueryAndAFragment(): void
    {
        self::assertTrue($this->validate('/über-uns', RelativeUrlRecord::class));
        self::assertTrue($this->validate('/about?page=2#team', RelativeUrlRecord::class));
        self::assertTrue($this->validate('?page=2', RelativeUrlRecord::class));
        self::assertTrue($this->validate('#team', RelativeUrlRecord::class));
        self::assertTrue($this->validate('https://example.com/about', RelativeUrlRecord::class));

        self::assertFalse($this->validate('/about us', RelativeUrlRecord::class));
        self::assertFalse($this->validate('//example.com', RelativeUrlRecord::class));
        self::assertFalse($this->validate('example.com', RelativeUrlRecord::class));

        self::assertFalse($this->validate('/about'));
    }

    public function testARelativeUrlFieldIsATextInput(): void
    {
        $record = RelativeUrlRecord::create();
        $field = $record->getCustomAttributeDefinitions()['link']->createField($record);

        self::assertStringNotContainsString('type="url"', (string)$field->render());
    }

    /**
     * @param class-string<UrlRecord> $class
     */
    private function validate(string $url, string $class = UrlRecord::class): bool
    {
        $record = $class::create();
        $record->link = $url;

        if ($record->validate(['link'])) {
            // Nothing rewrites the value any more, so what was typed is what is stored.
            self::assertSame($url, $record->link);
            return true;
        }

        return false;
    }
}

/**
 * @property string|null $link
 */
class UrlRecord extends ActiveRecord implements CustomAttributeInterface
{
    use CustomAttributesTrait;

    #[Override]
    public function getCustomAttributes(): array
    {
        return [UrlCustomAttribute::make('link')];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'url_custom_attribute_test';
    }
}

class RelativeUrlRecord extends UrlRecord
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [UrlCustomAttribute::make('link')->relative()];
    }
}

class SchemesUrlRecord extends UrlRecord
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [UrlCustomAttribute::make('link')->schemes(['ftp', 'sms'])];
    }
}
