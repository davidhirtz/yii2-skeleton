<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\GroupCustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\LinkCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\VisibleAttributeTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;
use yii\base\Model;

class LinkCustomAttributeTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(LinkRecord::tableName(), [
                'id' => 'pk',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(LinkRecord::tableName())
            ->execute();
    }

    public function testAnAbsoluteAndAPartialUrlAreAccepted(): void
    {
        self::assertTrue($this->validate('https://example.com/über-uns'));
        self::assertTrue($this->validate('mailto:info@example.com'));
        self::assertTrue($this->validate('tel:+4930123456'));
        self::assertTrue($this->validate('/über-uns'));
        self::assertTrue($this->validate('/über-uns?page=2#team'));
        self::assertTrue($this->validate('?page=2'));
        self::assertTrue($this->validate('#team'));
    }

    public function testAnythingElseIsRefused(): void
    {
        self::assertFalse($this->validate('example.com'));
        self::assertFalse($this->validate('//example.com'));
        self::assertFalse($this->validate('/about us'));
        self::assertFalse($this->validate('javascript:alert(1)'));
        self::assertFalse($this->validate('entry:12'));
    }

    public function testTheFieldIsATextInput(): void
    {
        $record = LinkRecord::create();
        $field = $record->getCustomAttributeDefinitions()['link']->createField($record);

        self::assertStringNotContainsString('type="url"', (string)$field->render());
    }

    public function testTheVisibleValueIsTheStoredOne(): void
    {
        $record = LinkRecord::create();
        $record->link = '/über-uns';

        self::assertSame('/über-uns', $record->getVisibleAttribute('link'));
    }

    public function testAnAttributeFormatsTheVisibleValueInTheLanguageAsked(): void
    {
        $record = FormattedLinkRecord::create();
        $record->link = 'link:Ångström 東京 🚀';

        self::assertSame('Ångström 東京 🚀 (de)', $record->getVisibleAttribute('link', 'de'));
        self::assertSame('Ångström 東京 🚀 ()', $record->getVisibleAttribute('link'));
        self::assertSame('link:Ångström 東京 🚀', $record->link);
    }

    public function testAGroupRowFormatsTheVisibleValueToo(): void
    {
        $record = FormattedLinkGroupRecord::create();
        $record->links = [['link' => 'link:Ågot']];

        $item = $record->getCustomAttributeItems('links')[0] ?? self::fail('No row.');

        self::assertSame('Ågot (de)', $item->getVisibleAttribute('link', 'de'));
    }

    private function validate(string $url): bool
    {
        $record = LinkRecord::create();
        $record->link = $url;

        if ($record->validate(['link'])) {
            self::assertSame($url, $record->link);
            return true;
        }

        return false;
    }
}

/**
 * @property string|null $link
 */
class LinkRecord extends ActiveRecord implements CustomAttributeInterface
{
    use CustomAttributesTrait;
    use I18nAttributesTrait;
    use VisibleAttributeTrait;

    #[Override]
    public function getCustomAttributes(): array
    {
        return [LinkCustomAttribute::make('link')];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'link_custom_attribute_test';
    }
}

class FormattedLinkRecord extends LinkRecord
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [FormattedLinkCustomAttribute::make('link')];
    }
}

/**
 * @property array<int, array<string, mixed>>|null $links
 */
class FormattedLinkGroupRecord extends LinkRecord
{
    #[Override]
    public function getCustomAttributes(): array
    {
        return [GroupCustomAttribute::make('links')->multiple()->attributes([FormattedLinkCustomAttribute::make('link')])];
    }
}

class FormattedLinkCustomAttribute extends LinkCustomAttribute
{
    #[Override]
    public function formatVisibleValue(Model $owner, mixed $value, ?string $language): mixed
    {
        return substr((string)$value, 5) . " ($language)";
    }
}
