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
