<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\collections\TrailModelCollection;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\TrailGridView;
use davidhirtz\yii2\skeleton\validators\DynamicRangeValidator;
use ReflectionClass;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;

/**
 * @property int $id
 * @property int $type
 * @property string $model
 * @property string $model_id
 * @property int|null $user_id
 * @property string $message
 * @property string|array|null $data
 * @property DateTime $created_at
 *
 * @property-read User $user {@see Trail::getUser}
 */
class Trail extends ActiveRecord
{
    use TypeAttributeTrait;

    public const TYPE_CREATE = 1;
    public const TYPE_UPDATE = 2;
    public const TYPE_DELETE = 3;
    public const TYPE_ASSIGN = 6;
    public const TYPE_REVOKE = 7;
    public const TYPE_CHILD_CREATE = 8;
    public const TYPE_CHILD_UPDATE = 9;
    public const TYPE_CHILD_DELETE = 10;
    public const TYPE_ORDER = 11;
    public const TYPE_PASSWORD = 12;

    public ActiveRecordInterface|array|null $parents = null;

    public function rules(): array
    {
        return [
            [
                ['type'],
                DynamicRangeValidator::class,
            ],
        ];
    }

    public function afterFind(): void
    {
        $this->data = $this->data ? json_decode($this->data, true, 512, JSON_THROW_ON_ERROR) : null;
        parent::afterFind();
    }

    public function beforeSave($insert): bool
    {
        if (Yii::$app->has('user')) {
            $this->user_id = Yii::$app->getUser()->getId();
        }

        if (is_array($this->model_id)) {
            $this->model_id = implode('-', $this->model_id);
        }

        $this->data = $this->data ? json_encode($this->data, JSON_THROW_ON_ERROR) : null;
        $this->created_at = new DateTime();

        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes): void
    {
        if ($this->parents) {
            if ($type = static::getTypes()[$this->type]['parentType'] ?? false) {
                $this->parents = array_filter(!is_array($this->parents) ? [$this->parents] : $this->parents);

                foreach ($this->parents as $parent) {
                    if (!$parent->isDeleted()) {
                        /** @var TrailBehavior $behavior */
                        if ($behavior = $parent->getBehavior('TrailBehavior')) {
                            $trail = new static();
                            $trail->model = $behavior->modelClass;
                            $trail->model_id = $parent->getPrimaryKey(true);
                            $trail->type = $type;

                            $trail->data = [
                                'model' => $this->model,
                                'model_id' => $this->model_id,
                                'trail_id' => $this->id,
                            ];

                            $trail->insert();
                        }
                    }
                }
            }
        }

        $this->data = $this->data ? json_decode($this->data, true, 512, JSON_THROW_ON_ERROR) : null;
        parent::afterSave($insert, $changedAttributes);
    }

    public function getUser(): UserQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getModelName(): string
    {
        if ($model = $this->getModelClass()) {
            if ($model->getBehavior('TrailBehavior')) {
                /** @var TrailBehavior $model */
                return $model->getTrailModelName();
            }

            return (new ReflectionClass($model))->getShortName();
        }

        return $this->model;
    }

    public function getModelType(): ?string
    {
        if (($model = $this->getModelClass()) && $model->getBehavior('TrailBehavior')) {
            /** @var TrailBehavior $model */
            return $model->getTrailModelType();
        }

        return null;
    }

    public function getModelClass(): ?Model
    {
        return TrailModelCollection::getModelByNameAndId($this->model, $this->model_id);
    }

    public function getDataModelClass(): ?Model
    {
        if (empty($this->data['model'])) {
            return null;
        }

        return TrailModelCollection::getModelByNameAndId($this->data['model'], $this->data['model_id'] ?? null);
    }

    /**
     * @return bool
     */
    public function isAuthPermissionType(): bool
    {
        return in_array($this->type, [static::TYPE_ASSIGN, static::TYPE_REVOKE]);
    }

    /**
     * @return bool
     */
    public function isAuthPermissionAssignType(): bool
    {
        return $this->type == static::TYPE_ASSIGN;
    }

    /**
     * @return bool
     */
    public function isDeleteType(): bool
    {
        return in_array($this->type, [static::TYPE_DELETE, static::TYPE_CHILD_DELETE]);
    }

    /**
     * @return bool
     */
    public function isCreateType(): bool
    {
        return $this->type == static::TYPE_CREATE;
    }

    /**
     * @return bool
     */
    public function isUpdateType(): bool
    {
        return $this->type == static::TYPE_UPDATE;
    }

    /**
     * @return bool
     */
    public function hasAttributesEnabled(): bool
    {
        return $this->isCreateType() || $this->isUpdateType();
    }

    /**
     * @return bool
     */
    public function hasDataModelEnabled(): bool
    {
        return $this->getTypeOptions()['hasDataModel'] ?? false;
    }

    public static function createOrderTrail(?ActiveRecordInterface $model, ?string $message = null, array $data = []): static
    {
        $trail = new static();
        $trail->type = static::TYPE_ORDER;

        if ($model) {
            /** @var TrailBehavior $behavior */
            $behavior = $model->getBehavior('TrailBehavior');
            $trail->model = $behavior->modelClass;
            $trail->model_id = $model->getPrimaryKey(true);
        }

        $trail->message = $message;
        $trail->data = $data;
        $trail->insert();

        return $trail;
    }

    public static function getAdminRouteByModel(?Model $model, int|string|null $id = null): array
    {
        if ($model) {
            /** @var TrailBehavior $behavior */
            $behavior = $model->getBehavior('TrailBehavior');

            if ($model instanceof ActiveRecord) {
                $id ??= implode('-', $model->getPrimaryKey(true));
            }

            $model = implode('@', array_filter([$behavior->modelClass, $id]));
        }

        return ['/admin/trail/index', 'model' => $model];
    }

    /**
     * The message translations are set via `Yii::t()` so the translation controller will pick them up. The actual
     * translation will happen in {@see TrailGridView}.
     */
    public static function getTypes(): array
    {
        $language = Yii::$app->sourceLanguage;

        return [
            static::TYPE_CREATE => [
                'name' => Yii::t('skeleton', 'Created'),
                'parentType' => static::TYPE_CHILD_CREATE,
                'icon' => 'plus',
            ],
            static::TYPE_UPDATE => [
                'name' => Yii::t('skeleton', 'Updated'),
                'parentType' => static::TYPE_CHILD_UPDATE,
                'icon' => 'pencil-alt',
            ],
            static::TYPE_DELETE => [
                'name' => Yii::t('skeleton', 'Deleted'),
                'message' => Yii::t('skeleton', '{model} was deleted', [], $language),
                'parentType' => static::TYPE_CHILD_DELETE,
                'icon' => 'trash-alt',
            ],
            static::TYPE_ASSIGN => [
                'name' => Yii::t('skeleton', 'Permission assigned'),
                'icon' => 'user-plus',
            ],
            static::TYPE_REVOKE => [
                'name' => Yii::t('skeleton', 'Permission revoked'),
                'icon' => 'user-minus',
            ],
            static::TYPE_CHILD_CREATE => [
                'name' => Yii::t('skeleton', 'Created'),
                'message' => Yii::t('skeleton', '{model} created', [], $language),
                'hasDataModel' => true,
                'icon' => 'plus',
            ],
            static::TYPE_CHILD_UPDATE => [
                'name' => Yii::t('skeleton', 'Updated'),
                'message' => Yii::t('skeleton', '{model} updated', [], $language),
                'hasDataModel' => true,
                'icon' => 'pencil-alt',
            ],
            static::TYPE_CHILD_DELETE => [
                'name' => Yii::t('skeleton', 'Deleted'),
                'message' => Yii::t('skeleton', '{model} deleted', [], $language),
                'hasDataModel' => true,
                'icon' => 'trash-alt',
            ],
            static::TYPE_ORDER => [
                'name' => Yii::t('skeleton', 'Ordered'),
                'icon' => 'sort-amount-down',
            ],
            static::TYPE_PASSWORD => [
                'name' => Yii::t('skeleton', 'Password changed'),
                'message' => Yii::t('skeleton', 'The password was changed'),
                'icon' => 'key',
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'model' => Yii::t('skeleton', 'Record'),
            'user_id' => Yii::t('skeleton', 'User'),
            'data' => Yii::t('skeleton', 'Updates'),
            'created_at' => Yii::t('skeleton', 'Time'),
        ];
    }

    public function formName(): string
    {
        return 'Trail';
    }

    public static function tableName(): string
    {
        return '{{%trail}}';
    }
}