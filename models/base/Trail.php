<?php

namespace davidhirtz\yii2\skeleton\models\base;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\User;
use ReflectionClass;
use Yii;
use yii\db\ActiveQuery;

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

    public const TYPE_INSERT = 1;
    public const TYPE_UPDATE = 2;
    public const TYPE_DELETE = 3;
    public const TYPE_LINK = 4;
    public const TYPE_UNLINK = 5;
    public const TYPE_ASSIGN = 6;
    public const TYPE_REVOKE = 7;
    public const TYPE_CHILD_INSERT = 8;
    public const TYPE_CHILD_UPDATE = 9;
    public const TYPE_CHILD_DELETE = 10;
    public const TYPE_ORDER = 11;
    public const TYPE_PASSWORD = 12;

    /**
     * @var ActiveRecord|ActiveRecord[]
     */
    public $related;

    /**
     * @var ActiveRecord|string
     */
    private $_modelClass;

    /**
     * @return array|array[]
     */
    public function rules()
    {
        return [
            [
                ['type'],
                'validateType',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function afterFind()
    {
        $this->data = json_decode($this->data, true);
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
        if ($this->related) {
            if ($type = static::getTypes()[$this->type]['parentType'] ?? false) {
                if (!is_array($this->related)) {
                    $this->related = [$this->related];
                }

                foreach ($this->related as $related) {
                    $trail = new static();
                    $trail->model = get_class($related);
                    $trail->model_id = implode('-', $related->getPrimaryKey(true));
                    $trail->type = $type;
                    $trail->data = ['model' => $this->model, 'model_id' => $this->model_id];
                    $trail->insert();
                }
            }
        }

        $this->data = json_decode($this->data, true);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
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
     * @return ActiveRecord|null
     */
    public function getModelClass()
    {
        if ($this->_modelClass === null) {
            /** @var ActiveRecord $model */
            $this->_modelClass = ($model = $this->model) ? ($model::findOne($this->model_id) ?: $model::instance()) : null;
        }

        return $this->_modelClass;
    }

    /**
     * @return array[]
     */
    public static function getTypes(): array
    {
        return [
            static::TYPE_INSERT => [
                'name' => Yii::t('skeleton', 'Created'),
                'parentType' => static::TYPE_CHILD_INSERT,
            ],
            static::TYPE_UPDATE => [
                'name' => Yii::t('skeleton', 'Updated'),
                'parentType' => static::TYPE_CHILD_UPDATE,
            ],
            static::TYPE_DELETE => [
                'name' => Yii::t('skeleton', 'Deleted'),
                'parentType' => static::TYPE_CHILD_DELETE,
            ],
            static::TYPE_LINK => [
                'name' => Yii::t('skeleton', 'Linked'),
            ],
            static::TYPE_UNLINK => [
                'name' => Yii::t('skeleton', 'Unlinked'),
            ],
            static::TYPE_ASSIGN => [
                'name' => Yii::t('skeleton', 'Permission assigned'),
            ],
            static::TYPE_REVOKE => [
                'name' => Yii::t('skeleton', 'Permission revoked'),
            ],
            static::TYPE_CHILD_INSERT => [
                'name' => Yii::t('skeleton', 'Child created'),
            ],
            static::TYPE_CHILD_UPDATE => [
                'name' => Yii::t('skeleton', 'Child updated'),
            ],
            static::TYPE_CHILD_DELETE => [
                'name' => Yii::t('skeleton', 'Child deleted'),
            ],
            static::TYPE_ORDER => [
                'name' => Yii::t('skeleton', 'Ordered'),
            ],
            static::TYPE_PASSWORD => [
                'name' => Yii::t('skeleton', 'Password changed'),
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'model' => Yii::t('skeleton', 'Object'),
            'user_id' => Yii::t('skeleton', 'User'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function formName()
    {
        return 'Trail';
    }

    /**
     * @inheritDoc
     */
    public static function tableName()
    {
        return '{{%trail}}';
    }
}