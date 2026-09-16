<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Db;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior;
use Hirtz\Skeleton\Behaviors\SearchBehavior;
use Hirtz\Skeleton\Db\Commands\BatchInsertQueryBuild;
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use Yii;
use davidhirtz\yii2\datetime\DateTime;
use yii\helpers\Inflector;

class ActiveRecord extends \yii\db\ActiveRecord
{
    use ModelTrait;

    private bool $isBatch = false;
    private bool $isDeleted = false;
    private bool $hasCustomAttributesColumn = true;

    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'AttributeTypecastBehavior' => AttributeTypecastBehavior::class,
            ...$this instanceof SearchableInterface ? ['SearchBehavior' => SearchBehavior::class] : [],
        ];
    }

    #[Override]
    public function beforeDelete(): bool
    {
        $this->isDeleted = true;
        return parent::beforeDelete();
    }

    /**
     * A form posts strings, and only validation typecast them until now — which a form reload never reaches. So a
     * `Closure` reading an attribute off the loaded record, {@see \Hirtz\Skeleton\Models\Types\Type::available()}
     * and its kind, saw `"2"` where the saved record holds `2`.
     *
     * @param array<string, mixed> $data
     */
    #[Override]
    public function load($data, $formName = null): bool
    {
        if (!parent::load($data, $formName)) {
            return false;
        }

        $behavior = $this->getBehavior('AttributeTypecastBehavior');

        if ($behavior instanceof AttributeTypecastBehavior) {
            $behavior->typecastAttributes();
        }

        return true;
    }

    /**
     * @return list<string> the attributes without a column of their own
     */
    public function getVirtualAttributes(): array
    {
        return [
            ...$this instanceof TranslationInterface ? array_keys($this->getTranslatedAttributeNames()) : [],
            ...$this instanceof CustomAttributeInterface ? $this->getCustomAttributeNames() : [],
        ];
    }

    public function getCustomAttributesColumn(): string
    {
        return 'custom_attributes';
    }

    /**
     * A record loaded without the column keeps the stored JSON: it is neither populated nor written back.
     */
    protected function populateCustomAttributes(): void
    {
        if (!$this instanceof CustomAttributeInterface) {
            return;
        }

        // `getAttributes()` reports every declared attribute; only the old attributes say what was actually selected.
        $column = $this->getCustomAttributesColumn();
        $this->hasCustomAttributesColumn = array_key_exists($column, $this->getOldAttributes());

        if (!$this->hasCustomAttributesColumn) {
            return;
        }

        $values = $this->getAttribute($column);
        $values = is_array($values) ? $values : [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            foreach ($definition->getAttributeNames($this) as $name) {
                $value = $definition->unserialize($values[$name] ?? null);

                $this->setAttribute($name, $value);
                $this->setOldAttribute($name, $value);
            }
        }
    }

    /**
     * Keeps a key no current definition claims, so switching a type back does not lose its values, and overwrites in
     * place because MySQL reorders the keys of a rebuilt object and every save would then read as changed.
     */
    protected function serializeCustomAttributes(): void
    {
        if (!$this instanceof CustomAttributeInterface || !$this->hasCustomAttributesColumn) {
            return;
        }

        $column = $this->getCustomAttributesColumn();
        $values = $this->getAttribute($column);
        $values = is_array($values) ? $values : [];

        foreach ($this->getCustomAttributeDefinitions() as $definition) {
            foreach ($definition->getAttributeNames($this) as $name) {
                $value = $definition->serialize($this->getAttribute($name));

                if ($value === null) {
                    unset($values[$name]);
                    continue;
                }

                $values[$name] = $value;
            }
        }

        $this->setAttribute($column, $values ?: null);
    }

    /**
     * @return array<string, mixed> the previous value per changed custom attribute, so the trail logs the attribute
     * rather than the JSON column
     */
    protected function getChangedCustomAttributes(bool $insert): array
    {
        if (!$this instanceof CustomAttributeInterface) {
            return [];
        }

        $changed = [];

        foreach ($this->getCustomAttributeNames() as $name) {
            $value = $this->getAttribute($name);
            $old = $insert ? null : $this->getOldAttribute($name);

            if ($value !== $old) {
                $changed[$name] = $old;
            }
        }

        return $changed;
    }

    /**
     * @return list<string>
     */
    public function getColumnAttributes(): array
    {
        return array_values(array_diff($this->attributes(), $this->getVirtualAttributes()));
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function attributes(): array
    {
        $virtual = $this->getVirtualAttributes();
        return array_values($virtual ? array_unique([...parent::attributes(), ...$virtual]) : parent::attributes());
    }

    /**
     * @param list<string>|null $attributes
     */
    #[Override]
    protected function insertInternal($attributes = null): bool
    {
        return parent::insertInternal($this->filterColumnAttributes($attributes));
    }

    /**
     * @param list<string>|null $attributes
     */
    #[Override]
    protected function updateInternal($attributes = null): false|int
    {
        // A virtual-only change touches no column, so Yii reports 0 affected rows even though a record was written.
        $virtual = $this->getDirtyAttributes($this->getVirtualAttributes());
        $result = parent::updateInternal($this->filterColumnAttributes($attributes));

        return $result === 0 && $virtual ? 1 : $result;
    }

    #[Override]
    public function afterFind(): void
    {
        $this->populateCustomAttributes();
        parent::afterFind();
    }

    /**
     * A refresh sets every virtual attribute to `null`; the old values follow so none reads as changed.
     */
    #[Override]
    public function afterRefresh(): void
    {
        if ($this instanceof TranslationInterface) {
            $this->resetLoadedTranslations();
        }

        $this->populateCustomAttributes();

        foreach ($this->getVirtualAttributes() as $name) {
            $this->setOldAttribute($name, $this->getAttribute($name));
        }

        parent::afterRefresh();
    }

    public function updateOldVirtualAttributes(): void
    {
        foreach ($this->getVirtualAttributes() as $name) {
            $this->setOldAttribute($name, $this->getAttribute($name));
        }

        if ($this instanceof TranslationInterface) {
            $this->markTranslationsLoaded($this->getTranslationLanguages());
        }
    }

    /**
     * @param list<string>|null $attributes
     * @return list<string>
     */
    private function filterColumnAttributes(?array $attributes): array
    {
        return $attributes === null
            ? $this->getColumnAttributes()
            : array_values(array_diff($attributes, $this->getVirtualAttributes()));
    }

    #[Override]
    public function beforeValidate(): bool
    {
        if ($this instanceof CustomAttributeInterface && $this->getIsNewRecord()) {
            $this->applyCustomAttributeDefaults();
        }

        return parent::beforeValidate();
    }

    /**
     * Serializes after the event, so a behavior writing an attribute in `EVENT_BEFORE_*` is included.
     */
    #[Override]
    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->serializeCustomAttributes();

        return true;
    }

    /**
     * The virtual attributes are written here rather than from a behavior, so their changes reach the event the
     * trail listens to without depending on the order two behaviors were attached in.
     *
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if ($this instanceof TranslationInterface) {
            $changedAttributes = [...$changedAttributes, ...$this->saveVirtualAttributes()];
        }

        $changedCustomAttributes = $this->getChangedCustomAttributes($insert);
        $changedAttributes = [...$changedAttributes, ...$changedCustomAttributes];

        // Before the old values are updated: a definition with a side effect of its own, such as a file it has to
        // move into place now that the record has an id, rewrites the attribute here.
        if ($this instanceof CustomAttributeInterface) {
            foreach ($changedCustomAttributes as $name => $old) {
                $this->getCustomAttribute($name)?->afterSave($this, $name, $old);
            }
        }

        $this->updateOldVirtualAttributes();

        parent::afterSave($insert, $changedAttributes);
    }

    #[Override]
    public function afterDelete(): void
    {
        if ($this instanceof TranslationInterface) {
            $this->deleteVirtualAttributes();
        }

        if ($this instanceof CustomAttributeInterface) {
            foreach ($this->getCustomAttributeDefinitions() as $definition) {
                foreach ($definition->getAttributeNames($this) as $name) {
                    $definition->afterDelete($this, $name);
                }
            }
        }

        parent::afterDelete();
    }

    /**
     * @return ActiveQuery<static>
     */
    #[Override]
    public static function find(): ActiveQuery
    {
        return Yii::createObject(ActiveQuery::class, [static::class]);
    }

    /**
     * Through the container, so a definition such as `i18nAttributes` also applies to loaded records.
     *
     * @param array<string, mixed> $row
     */
    #[Override]
    public static function instantiate($row): static
    {
        return static::create();
    }

    #[Override]
    public static function findOne($condition): ?static
    {
        return $condition === null ? null : parent::findOne($condition);
    }

    /**
     * @return ActiveQuery<ActiveRecord>|null
     */
    public function getRelationFromForeignKey(string $foreignKey, bool $throwException = false): ?ActiveQuery
    {
        /** @var ActiveQuery<ActiveRecord>|null $query */
        $query = $this->getRelation($this->getRelationNameFromForeignKey($foreignKey), $throwException);
        return $query;
    }

    public function getRelationNameFromForeignKey(string $foreignKey): string
    {
        return lcfirst(Inflector::camelize(str_replace('_id', '', $foreignKey)));
    }

    /**
     * @return ActiveRecord|array<int|string, ActiveRecord>|null
     */
    public function refreshRelation(string $name): ActiveRecord|array|null
    {
        /** @var ActiveQuery<ActiveRecord> $query */
        $query = $this->getRelation($name);
        $method = $query->multiple ? 'all' : 'one';

        $related = $query->{$method}();
        $this->populateRelation($name, $related);

        return $related;
    }

    /**
     * @param array<int|string, mixed> $attributes
     */
    public function updateAttributesBlameable(array $attributes): int
    {
        foreach ($attributes as $name => $value) {
            if (is_int($name)) {
                if ($value === 'updated_by_user_id') {
                    $attributes[$value] = WebUser::current()?->getId();
                    unset($name);
                }

                if ($value === 'updated_at') {
                    $attributes[$value] = new DateTime();
                    unset($name);
                }
            }
        }

        return $this->updateAttributes($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function upsert(bool $runValidation = true, ?array $attributes = null): bool
    {
        return !$this->getIsNewRecord()
            ? $this->update($runValidation, $attributes) === 1
            : $this->insert($runValidation, $attributes);
    }

    /**
     * @param list<string>|list<array<string, mixed>> $columns
     * @param list<array<string, mixed>>|null $rows
     */
    public static function batchInsert(array $columns, ?array $rows = null, bool $ignore = false): int
    {
        $query = Yii::createObject(BatchInsertQueryBuild::class, [static::class, ...func_get_args()]);
        return $query->command->execute();
    }

    /**
     * Extends the default functionality by checking for DateTime objects, which unfortunately cannot be compared by
     * checking identical values using `===` as it always returns `true` even if the date was not changed.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function getDirtyAttributes($names = null): array
    {
        return array_filter(parent::getDirtyAttributes($names), function ($name): bool {
            $new = $this->getAttribute($name);
            $old = $this->getOldAttribute($name);

            return !$new instanceof \DateTime
                || !$old instanceof \DateTime
                || $new->getTimestamp() !== $old->getTimestamp();
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Extends the default functionality by setting $identical to `false` for DateTime objects, which unfortunately
     * cannot be compared by checking identical values using `===` as it always returns `true` even if the date was not
     * changed.
     */
    #[Override]
    public function isAttributeChanged($name, $identical = true): bool
    {
        if ($this->getAttribute($name) instanceof \DateTime) {
            $identical = false;
        }

        return parent::isAttributeChanged($name, $identical);
    }

    /**
     * @param list<string> $attributeNames
     */
    public function hasChangedAttributes(array $attributeNames, bool $identical = true): bool
    {
        foreach ($attributeNames as $attribute) {
            if ($this->isAttributeChanged($attribute, $identical)) {
                return true;
            }
        }

        return false;
    }

    public function setIsBatch(bool $isBatch): void
    {
        $this->isBatch = $isBatch;
    }

    public function getIsBatch(): bool
    {
        return $this->isBatch;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    #[Override]
    public function rules(): array
    {
        return $this instanceof CustomAttributeInterface ? $this->getCustomAttributeRules() : [];
    }

    #[Override]
    public function attributeHints(): array
    {
        return $this instanceof CustomAttributeInterface
            ? array_filter($this->getCustomAttributeHints(), static fn (?string $hint): bool => $hint !== null)
            : [];
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...$this instanceof CustomAttributeInterface ? $this->getCustomAttributeLabels() : [],
            'id' => Yii::t('skeleton', 'COMMON_ID_LABEL'),
            'status' => Yii::t('skeleton', 'COMMON_STATUS_LABEL'),
            'type' => Yii::t('skeleton', 'COMMON_TYPE_LABEL'),
            'updated_by_user_id' => Yii::t('skeleton', 'COMMON_UPDATED_BY_USER_ID_LABEL'),
            'updated_at' => Yii::t('skeleton', 'COMMON_UPDATED_AT_LABEL'),
            'created_at' => Yii::t('skeleton', 'COMMON_CREATED_AT_LABEL'),
        ];
    }
}
