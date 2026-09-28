<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Actions;

use davidhirtz\yii2\datetime\DateTime;
use DateTimeZone;
use Exception;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\CustomAttributes\UploadCustomAttribute;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;

/**
 * Writes the values an update replaced back, through the record's own `load()` and `save()`: they are cast and
 * validated like an editor's, and the restore is a trail entry of its own. Only what the record still accepts is
 * restored — an attribute it dropped or no longer lets a form set is skipped, and so is an upload, whose earlier
 * file was deleted when it was replaced. Relations, positions and deletions are not replayed.
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

    public static function isRestorable(Trail $trail): bool
    {
        return $trail->type === Trail::TYPE_UPDATE
            && is_array($trail->data)
            && $trail->data !== []
            && ($model = $trail->getModelRecord()) instanceof ActiveRecord
            && $model instanceof TrailModelInterface
            && !$model instanceof User;
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

            $values[$attribute] = $this->normalize($change[0]);
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
     * A date went through `json_encode()` into the trail, which wrote PHP's own shape of it.
     */
    protected function normalize(mixed $value): mixed
    {
        if (is_array($value) && isset($value['date'], $value['timezone']) && is_string($value['date'])) {
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
