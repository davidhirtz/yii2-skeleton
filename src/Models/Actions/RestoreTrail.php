<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Actions;

use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Db\DateTime;
use DateTimeZone;
use Exception;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;

/**
 * Restores through the record's own `load()` and `save()`, so only what it still accepts comes back, and an upload,
 * whose earlier file is gone, is skipped.
 */
class RestoreTrail
{
    /**
     * @var list<string>
     */
    private array $restored = [];

    /**
     * @var list<string>
     */
    private array $skipped = [];

    private ?ActiveRecord $model = null;

    public function __construct(protected readonly Trail $trail)
    {
    }

    /**
     * A model without an admin page, the configuration having switched it off among others, is not restored.
     */
    public static function isRestorable(Trail $trail): bool
    {
        return $trail->type === Trail::TYPE_UPDATE
            && is_array($trail->data)
            && $trail->data !== []
            && ($model = $trail->getModelRecord()) instanceof ActiveRecord
            // A deleted record comes back as a new one with its id, which a save would insert.
            && !$model->getIsNewRecord()
            && $model instanceof TrailModelInterface
            && !$model instanceof User
            && $model->getAdminRoute() !== false;
    }

    public function run(): bool
    {
        if (!static::isRestorable($this->trail)) {
            return false;
        }

        $model = $this->trail->getModelRecord();
        assert($model instanceof ActiveRecord);
        $this->model = $model;

        $values = [];

        foreach ((array)$this->trail->data as $attribute => $change) {
            if (!is_array($change) || !array_key_exists(0, $change)) {
                continue;
            }

            if (!$model->isAttributeSafe($attribute) || $this->isUpload($model, $attribute)) {
                $this->skipped[] = $attribute;
                continue;
            }

            $values[$attribute] = $this->normalize($model, $attribute, $change[0]);
        }

        if (!$values) {
            return false;
        }

        $model->load([$model->formName() => $values]);

        if (!$model->save()) {
            return false;
        }

        $this->restored = array_keys($values);
        return true;
    }

    /**
     * @return list<string>
     */
    public function getRestored(): array
    {
        return $this->restored;
    }

    /**
     * @return list<string>
     */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    public function getModel(): ?ActiveRecord
    {
        return $this->model;
    }

    /**
     * A date went into the trail through `json_encode()` in PHP's own shape, which a custom attribute reads itself.
     */
    protected function normalize(ActiveRecord $model, string $attribute, mixed $value): mixed
    {
        if ($model instanceof CustomAttributeInterface && isset($model->getCustomAttributeDefinitions()[$attribute])) {
            return $value;
        }

        if (is_array($value) && isset($value['date'], $value['timezone']) && is_string($value['date'])) {
            $behavior = $model->getBehavior('AttributeTypecastBehavior');

            if ($behavior instanceof AttributeTypecastBehavior && ($behavior->getDateAttributes()[$attribute] ?? null) === AttributeTypecastBehavior::TYPE_DATE) {
                return substr($value['date'], 0, 10);
            }

            try {
                return new DateTime($value['date'], new DateTimeZone((string)$value['timezone']));
            } catch (Exception) {
                return null;
            }
        }

        return $value;
    }

    protected function isUpload(ActiveRecord $model, string $attribute): bool
    {
        return $model instanceof CustomAttributeInterface
            && ($model->getCustomAttributeDefinitions()[$attribute] ?? null) instanceof UploadCustomAttribute;
    }
}
