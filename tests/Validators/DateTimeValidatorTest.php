<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Validators;

use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Validators\DateTimeValidator;
use Override;
use yii\base\Model;

class DateTimeValidatorTest extends TestCase
{
    public function testAModelAttributeIsCastToTheValidatorsType(): void
    {
        $model = new DateTimeValidatorModel();
        $model->load(['publishedAt' => '2024-06-15 14:30', 'day' => '2024-06-15 14:30'], '');

        self::assertTrue($model->validate(), print_r($model->getErrors(), true));
        self::assertInstanceOf(DateTime::class, $model->publishedAt);
        self::assertSame('2024-06-15 14:30:00', (string)$model->publishedAt);
        self::assertInstanceOf(Date::class, $model->day);
        self::assertSame('2024-06-15', (string)$model->day);
    }

    public function testAStringIsInvalid(): void
    {
        $model = new DateTimeValidatorModel();
        $model->load(['publishedAt' => 'abc'], '');

        self::assertFalse($model->validate());
        self::assertSame('The format of Published At is invalid.', $model->getFirstError('publishedAt'));
    }

    public function testAnEmptyValueIsSkipped(): void
    {
        $model = new DateTimeValidatorModel();
        $model->load(['publishedAt' => ''], '');

        self::assertTrue($model->validate(), print_r($model->getErrors(), true));
    }

    public function testAModelWithoutTheBehaviorNeverPasses(): void
    {
        $validator = new DateTimeValidator();

        self::assertFalse($validator->validate('2024-06-15'));
        self::assertTrue($validator->validate(new DateTime('2024-06-15')));
    }
}

class DateTimeValidatorModel extends Model
{
    public mixed $publishedAt = null;
    public mixed $day = null;

    #[Override]
    public function behaviors(): array
    {
        return [
            'AttributeTypecastBehavior' => AttributeTypecastBehavior::class,
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['publishedAt'],
                DateTimeValidator::class,
            ],
            [
                ['day'],
                DateTimeValidator::class,
                'type' => AttributeTypecastBehavior::TYPE_DATE,
            ],
        ];
    }
}
