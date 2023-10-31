<?php

namespace davidhirtz\yii2\skeleton\models\base;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TrailGridView;
use ReflectionClass;
use Yii;
use yii\db\ActiveRecordInterface;

/**
 * Class Trail
 * @package davidhirtz\yii2\skeleton\models\base
 *
 * @property int $id
 * @property int $type
 * @property string $model
 * @property string $model_id
 * @property int|null $user_id
 * @property string $message
 * @property string|array|null $data
 * @property DateTime $created_at
 *
 * @property User $user {@link \davidhirtz\yii2\skeleton\models\Trail::getUser()}
 *
 * @method static \davidhirtz\yii2\skeleton\models\Trail findOne($condition)
 * @method static \davidhirtz\yii2\skeleton\models\Trail[] findAll($condition)
 */
abstract class Trail extends ActiveRecord
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

    /**
     * @var ActiveRecord|ActiveRecord[]
     */
    public $parents;

    /**
     * @var ActiveRecord[]
     */
    private static $_modelClasses;

    /**
     * @return array|array[]
     */
    public function rules()
    {
        return [
            [
                ['type'],
                'davidhirtz\yii2\skeleton\validators\DynamicRangeValidator',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function afterFind()
    {
        $this->data = $this->data ? json_decode($this->data, true) : null;
        parent::afterFind();
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (Yii::$app->has('user')) {
            $this->user_id = Yii::$app->getUser()->getId();
        }

        if (is_array($this->model_id)) {
            $this->model_id = implode('-', $this->model_id);
        }

        $this->data = $this->data ? json_encode($this->data) : null;
        $this->created_at = new DateTime();

        return parent::beforeSave($insert);
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
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

        $this->data = $this->data ? json_decode($this->data, true) : null;
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return UserQuery
     */
    public function getUser()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return string
     */
    public function getModelName()
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

    /**
     * @return string|void|null
     */
    public function getModelType()
    {
        if (($model = $this->getModelClass()) && $model->getBehavior('TrailBehavior')) {
            /** @var TrailBehavior $model */
            return $model->getTrailModelType();
        }

        return null;
    }

    /**
     * @return ActiveRecord|null
     */
    public function getModelClass()
    {
        return static::getModelByTrail($this->model, $this->model_id);
    }

    /**
     * @return ActiveRecord|null
     */
    public function getDataModelClass()
    {
        return static::getModelByTrail($this->data['model'] ?? null, $this->data['model_id'] ?? null);
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

    /**
     * @param ActiveRecord|null $model
     * @param string|null $message
     * @param array|null $data
     * @return static
     */
    public static function createOrderTrail($model, $message = null, $data = [])
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

    /**
     * @param string $model
     * @param string $modelId
     * @return mixed
     */
    public static function getModelByTrail($model, $modelId)
    {
        if (!isset(static::$_modelClasses[$model][$modelId])) {
            static::$_modelClasses[$model][$modelId] = $model ? static::findModelById($model, $modelId) : null;
        }

        return static::$_modelClasses[$model][$modelId];
    }

    /**
     * Finds the model based on the given model string. If a model supports `i18n` tables, the corresponding language
     * will be added to the model, separated by "::" like `\davidhirtz\yii2\cms\models\Entry::en_US`.
     */
    protected static function findModelById(string $model, int|string $modelId): ?ActiveRecord
    {
        $model = explode('::', $model);
        $language = $model[1] ?? Yii::$app->language;

        return Yii::$app->getI18n()->callback($language, function () use ($model, $modelId) {
            $instance = Yii::createObject($model[0]);

            if ($instance instanceof ActiveRecord) {
                // Prevent PHP warnings if record has a primary key mismatch
                if ($keys = @array_combine($instance::primaryKey(), explode('-', $modelId))) {
                    $instance = $instance::findOne($keys) ?? $instance;
                }
            }

            return $instance;
        });
    }

    public static function getAdminRouteByModel(?ActiveRecord $model, int|string|null $id = null): array
    {
        if ($model) {
            /** @var TrailBehavior $behavior */
            $behavior = $model->getBehavior('TrailBehavior');
            $model = implode('@', array_filter([$behavior->modelClass, $id ?: implode('-', $model->getPrimaryKey(true))]));
        }

        return ['/admin/trail/index', 'model' => $model];
    }

    /**
     * The message translations are set via `Yii::t()` so the translation controller will pick them up. The actual
     * translation will happen in {@link TrailGridView}.
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
        return array_merge(parent::attributeLabels(), [
            'model' => Yii::t('skeleton', 'Record'),
            'user_id' => Yii::t('skeleton', 'User'),
            'data' => Yii::t('skeleton', 'Updates'),
            'created_at' => Yii::t('skeleton', 'Time'),
        ]);
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