<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\CustomAttributes;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\EmailCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class EmailCustomAttributeTest extends TestCase
{
    #[Override]
    protected function setUpSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->createTable(EmailRecord::tableName(), [
                'id' => 'pk',
                'custom_attributes' => 'json null',
            ])
            ->execute();
    }

    #[Override]
    protected function tearDownSchema(): void
    {
        Yii::$app->getDb()->createCommand()
            ->dropTable(EmailRecord::tableName())
            ->execute();
    }

    public function testOnlyAnEmailAddressValidates(): void
    {
        $record = EmailRecord::create();

        $record->contact = 'mail@example.com';
        self::assertTrue($record->validate(['contact']));

        $record->contact = 'not an address';
        self::assertFalse($record->validate(['contact']));
    }

    public function testTheFieldIsAnEmailInput(): void
    {
        $record = EmailRecord::create();
        $field = $record->getCustomAttributeDefinitions()['contact']->createField($record);

        self::assertStringContainsString('type="email"', (string)$field->render());
    }
}

/**
 * @property string|null $contact
 */
class EmailRecord extends ActiveRecord implements CustomAttributeInterface
{
    use CustomAttributesTrait;

    #[Override]
    public function getCustomAttributes(): array
    {
        return [EmailCustomAttribute::make('contact')];
    }

    #[Override]
    public static function tableName(): string
    {
        return 'email_custom_attribute_test';
    }
}
