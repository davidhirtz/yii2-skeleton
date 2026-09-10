<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Validators;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Db\I18nActiveQuery;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Yii;
use yii\base\InvalidConfigException;

class UniqueValidator extends \yii\validators\UniqueValidator
{
    /**
     * Extends the default `unique` validator by adding a default `when` check, that prevents database queries when
     * the attributes haven't changed.
     */
    #[\Override]
    public function init(): void
    {
        $this->when ??= function (ActiveRecord $model, $attribute): bool {
            if (is_array($this->targetAttribute) && count($this->targetAttribute) > 1) {
                return count($model->getDirtyAttributes($this->targetAttribute)) > 0;
            }

            return $model->hasChangedAttributes((array)($this->targetAttribute ?: $attribute));
        };

        if (!$this->message) {
            $this->message = Yii::t('yii', '{attribute} "{value}" has already been taken.');
        }

        parent::init();
    }

    /**
     * A translated attribute has no column, so Yii's query builder — whose relevant methods are all private — cannot
     * find it. The check is rebuilt here against the expression the value is actually stored as.
     */
    #[\Override]
    public function validateAttribute($model, $attribute): void
    {
        $targetAttributes = $this->getNormalizedTargetAttributes($attribute);

        if (
            !$model instanceof ActiveRecord
            || !$model instanceof TranslationInterface
            || !array_intersect($targetAttributes, array_keys($model->getTranslatedAttributeNames()))
        ) {
            parent::validateAttribute($model, $attribute);
            return;
        }

        if ($this->skipOnError) {
            foreach (array_keys($targetAttributes) as $modelAttribute) {
                if ($model->hasErrors($modelAttribute)) {
                    return;
                }
            }
        }

        if ($this->getQuery($model, $targetAttributes)->exists()) {
            $this->addError($model, $attribute, count($targetAttributes) > 1
                ? $this->comboNotUnique ?: $this->message
                : $this->message);
        }
    }

    /**
     * @param array<string, string> $targetAttributes
     * @return I18nActiveQuery<ActiveRecord>
     */
    protected function getQuery(ActiveRecord&TranslationInterface $model, array $targetAttributes): I18nActiveQuery
    {
        /** @var class-string<ActiveRecord> $targetClass */
        $targetClass = $this->targetClass ?? $model::class;
        $query = $targetClass::find();

        if (!$query instanceof I18nActiveQuery) {
            throw new InvalidConfigException("$targetClass::find() must return an " . I18nActiveQuery::class . '.');
        }

        $names = $model->getTranslatedAttributeNames();
        $alias = $query->getTableAlias();

        foreach ($targetAttributes as $modelAttribute => $targetAttribute) {
            // A translated attribute competes with the same language only; keeping a URL unique is the permalink's job.
            $column = isset($names[$targetAttribute])
                ? $query->getI18nAttributeName(...$names[$targetAttribute])
                : "$alias.[[$targetAttribute]]";

            $query->andWhere([$column => $model->$modelAttribute]);
        }

        if (!$model->getIsNewRecord()) {
            $condition = [];

            foreach ((array)$model->getOldPrimaryKey(true) as $name => $value) {
                $condition["$alias.[[$name]]"] = $value;
            }

            $query->andWhere(['not', $condition]);
        }

        if (is_string($this->filter) || is_array($this->filter)) {
            $query->andWhere($this->filter);
        } elseif ($this->filter !== null) {
            call_user_func($this->filter, $query);
        }

        return $query;
    }

    /**
     * @return array<string, string> the target attribute per model attribute, the way Yii reads `targetAttribute`
     */
    protected function getNormalizedTargetAttributes(string $attribute): array
    {
        $targetAttribute = $this->targetAttribute ?? $attribute;

        if (!is_array($targetAttribute)) {
            return [$targetAttribute => $targetAttribute];
        }

        $attributes = [];

        foreach ($targetAttribute as $key => $value) {
            $attributes[is_int($key) ? $value : $key] = $value;
        }

        return $attributes;
    }
}
