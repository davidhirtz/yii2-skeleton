<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Actions;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Translation;

class SaveTranslations
{
    public function __construct(
        protected ActiveRecord&TranslationInterface $model,
    ) {
    }

    /**
     * Diffs against the stored records, so a value written via `setAttribute()` without a lazy load is still detected.
     *
     * @return array<string, string|null> the previous value per changed translated attribute name
     */
    public function save(): array
    {
        $names = $this->model->getTranslatedAttributeNames();
        $dirty = $this->model->getDirtyAttributes(array_keys($names));

        if (!$dirty) {
            return [];
        }

        $changes = [];
        $records = $this->findTranslations(array_intersect_key($names, $dirty));

        foreach (array_keys($dirty) as $name) {
            [$attribute, $language] = $names[$name];

            $record = $records["$language/$attribute"] ?? null;
            $previous = $record?->value;
            $value = $this->normalizeValue($this->model->getAttribute($name));

            if ($value === $previous) {
                continue;
            }

            if ($value === null) {
                $record?->delete();
            } else {
                $this->upsert($language, $attribute, $value);
            }

            $changes[$name] = $previous;
        }

        if ($changes && $this->model->isRelationPopulated('translations')) {
            $this->model->refreshRelation('translations');
        }

        return $changes;
    }

    protected function upsert(string $language, string $attribute, string $value): void
    {
        Translation::getDb()->createCommand()
            ->upsert(Translation::tableName(), [
                'model' => $this->model->getTranslationModelClass(),
                'model_id' => $this->model->getPrimaryKey(),
                'language' => $language,
                'attribute' => $attribute,
                'value' => $value,
            ], ['value' => $value])
            ->execute();
    }

    /**
     * @param array<string, array{string, string}> $names
     * @return array<string, Translation>
     */
    protected function findTranslations(array $names): array
    {
        $records = Translation::find()
            ->whereModel($this->model->getTranslationModelClass(), (int)$this->model->getPrimaryKey())
            ->whereLanguage(array_values(array_unique(array_column($names, 1))))
            ->whereAttribute(array_values(array_unique(array_column($names, 0))))
            ->all();

        $result = [];

        foreach ($records as $record) {
            $result["$record->language/$record->attribute"] = $record;
        }

        return $result;
    }

    protected function normalizeValue(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string)$value;
    }
}
