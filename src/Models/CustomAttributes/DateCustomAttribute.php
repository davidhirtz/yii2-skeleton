<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use DateTimeInterface;
use DateTimeZone;
use Exception;
use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\Date;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Validators\DateTimeValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use Yii;
use yii\base\Model;

class DateCustomAttribute extends DateTimeCustomAttribute
{
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [
            ['filter', 'filter' => $this->normalize(...), 'skipOnArray' => false],
            [DateTimeValidator::class, 'type' => AttributeTypecastBehavior::TYPE_DATE],
        ];
    }

    #[Override]
    public function serialize(mixed $value): mixed
    {
        $value = $this->normalize($value);
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(InputField::make()->type('date'), $owner);
    }

    #[Override]
    protected function formatDate(DateTimeInterface $date): string
    {
        return Yii::$app->getFormatter()->asDate($date, 'medium');
    }

    #[Override]
    protected function createDate(mixed $value): ?DateTimeInterface
    {
        $timeZone = new DateTimeZone(Yii::$app->getTimeZone());

        if ($value instanceof Date) {
            return $value;
        }

        if (is_int($value)) {
            $value = new DateTime("@$value");
        }

        if ($value instanceof DateTimeInterface) {
            return new Date(DateTime::createFromInterface($value)->setTimezone($timeZone)->format('Y-m-d'), $timeZone);
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = new Date($value, $timeZone);
        } catch (Exception) {
            return null;
        }

        return Date::getLastErrors() ? null : $date->setTime(0, 0);
    }
}
