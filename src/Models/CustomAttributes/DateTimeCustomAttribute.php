<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use DateTimeInterface;
use DateTimeZone;
use Exception;
use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Validators\DateTimeValidator;
use Hirtz\Skeleton\Widgets\Forms\Fields\DateTimeField;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Override;
use Yii;
use yii\base\Model;
use yii\db\BaseActiveRecord;

/**
 * A date and time, held as a {@see DateTime} in the application's time zone and stored in UTC with its offset, so the
 * JSON reads the same whatever zone wrote it. {@see AttributeTypecastBehavior} only casts columns: the cast is a
 * filter of the attribute's own, ahead of the rule, and a date that writes what the record holds is the old instance,
 * so it does not read as changed.
 */
class DateTimeCustomAttribute extends CustomAttribute
{
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [
            ['filter', 'filter' => $this->normalize(...), 'skipOnArray' => false],
            [DateTimeValidator::class, 'type' => AttributeTypecastBehavior::TYPE_DATETIME],
        ];
    }

    /**
     * @return list<array>
     */
    #[Override]
    public function getRules(Model $owner): array
    {
        $rules = parent::getRules($owner);

        if (!$owner instanceof BaseActiveRecord || !$this->isVisible($owner) || $this->isDisabled($owner)) {
            return $rules;
        }

        foreach ($this->getAttributeNames($owner) as $name) {
            $rules[] = [$name, 'filter', 'filter' => fn (mixed $value): mixed => $this->keepOldValue($owner, $name, $value)];
        }

        return $rules;
    }

    /**
     * A value that cannot be read stays as it is, for the rule to refuse and the field to show.
     */
    #[Override]
    public function normalize(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->createDate($value) ?? $value;
    }

    #[Override]
    public function serialize(mixed $value): mixed
    {
        $value = $this->normalize($value);

        return $value instanceof DateTimeInterface
            ? DateTime::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM)
            : $value;
    }

    #[Override]
    public function unserialize(mixed $value): mixed
    {
        return $this->normalize($value);
    }

    /**
     * The trail stores a date as the array `json_encode()` makes of it.
     */
    #[Override]
    public function formatValue(Model $owner, mixed $value): ?string
    {
        if (is_array($value) && is_string($value['date'] ?? null)) {
            $timeZone = is_string($value['timezone'] ?? null) ? $value['timezone'] : 'UTC';
            $value = new DateTime($value['date'], new DateTimeZone($timeZone));
        }

        $value = $this->normalize($value);

        return match (true) {
            $value === null => null,
            $value instanceof DateTimeInterface => $this->formatDate($value),
            is_scalar($value) => (string)$value,
            default => null,
        };
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(DateTimeField::make(), $owner);
    }

    protected function formatDate(DateTimeInterface $date): string
    {
        return Yii::$app->getFormatter()->asDatetime($date, 'medium');
    }

    /**
     * A string is read in the application's time zone, which is what an editor typed, unless it names an offset, as
     * a stored value does. One PHP reads only by rolling it over, such as `2024-02-31`, is no date.
     */
    protected function createDate(mixed $value): ?DateTimeInterface
    {
        $timeZone = new DateTimeZone(Yii::$app->getTimeZone());

        if ($value instanceof DateTime) {
            return $value;
        }

        if (is_int($value)) {
            return (new DateTime("@$value"))->setTimezone($timeZone);
        }

        if ($value instanceof DateTimeInterface) {
            return DateTime::createFromInterface($value)->setTimezone($timeZone);
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = new DateTime($value, $timeZone);
        } catch (Exception) {
            return null;
        }

        return DateTime::getLastErrors() ? null : $date->setTimezone($timeZone);
    }

    private function keepOldValue(BaseActiveRecord $owner, string $name, mixed $value): mixed
    {
        $old = $owner->getOldAttribute($name);

        return $value instanceof DateTimeInterface
            && $old instanceof $value
            && $this->serialize($old) === $this->serialize($value)
            ? $old
            : $value;
    }
}
