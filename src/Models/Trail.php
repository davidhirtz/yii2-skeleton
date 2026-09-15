<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\TrailType;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\TrailGridView;
use Hirtz\Skeleton\Validators\DynamicRangeValidator;
use Override;
use ReflectionClass;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;

/**
 * @property int $id
 * @property int $type
 * @property string|null $model_class
 * @property array|string|null $model_id
 * @property int|null $user_id
 * @property string|null $message
 * @property array|null $data
 * @property DateTime $created_at
 *
 * @property-read User|null $user {@see Trail::getUser}
 * @property array<string, mixed>|null $data
 * @property list<int|string>|int|string|null $model_id
 */
class Trail extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    final public const string AUTH_TRAIL_INDEX = 'trailIndex';

    /**
     * A plain message, and the type of a trail that names none.
     */
    final public const int TYPE_DEFAULT = 13;

    final public const int TYPE_CREATE = 1;
    final public const int TYPE_UPDATE = 2;
    final public const int TYPE_DELETE = 3;
    final public const int TYPE_ASSIGN = 6;
    final public const int TYPE_REVOKE = 7;
    final public const int TYPE_CHILD_CREATE = 8;
    final public const int TYPE_CHILD_UPDATE = 9;
    final public const int TYPE_CHILD_DELETE = 10;
    final public const int TYPE_ORDER = 11;
    final public const int TYPE_PASSWORD = 12;

    /**
     * @var ActiveRecordInterface|array<int|string, mixed>|null
     */
    public ActiveRecordInterface|array|null $parents = null;

    #[Override]
    public function rules(): array
    {
        return [
            [
                ['type'],
                DynamicRangeValidator::class,
            ],
        ];
    }

    #[Override]
    public function beforeValidate(): bool
    {
        $this->type ??= static::TYPE_DEFAULT;
        return parent::beforeValidate();
    }

    #[Override]
    public function beforeSave($insert): bool
    {
        if (Yii::$app->has('user')) {
            $this->user_id = Yii::$app->getUser()->getId();
        }

        if (is_array($this->model_id)) {
            $this->model_id = implode('-', $this->model_id);
        }

        $this->created_at = new DateTime();

        return parent::beforeSave($insert);
    }

    /**
     * @param array<string, mixed> $changedAttributes
     */
    #[Override]
    public function afterSave($insert, $changedAttributes): void
    {
        if ($this->parents) {
            if ($type = $this->getType()?->getParentType()) {
                $this->parents = array_filter(!is_array($this->parents) ? [$this->parents] : $this->parents);

                foreach ($this->parents as $parent) {
                    if (!$parent->isDeleted()) {
                        if ($parent instanceof TrailModelInterface) {
                            $trail = static::create();
                            $trail->model_class = $parent->getTrailBehavior()->modelClass;
                            $trail->model_id = $parent instanceof ActiveRecordInterface ? $parent->getPrimaryKey(true) : null;
                            $trail->type = $type;

                            $trail->data = [
                                'model_class' => $this->model_class,
                                'model_id' => $this->model_id,
                                'trail_id' => $this->id,
                            ];

                            $trail->insert();
                        }
                    }
                }
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return UserQuery<User>
     */
    public function getUser(): UserQuery
    {
        /** @var UserQuery<User> $query */
        $query = $this->hasOne(User::class, ['id' => 'user_id']);
        return $query;
    }

    public function getModelName(): string
    {
        if ($model = $this->getModelRecord()) {
            return $model instanceof TrailModelInterface
                ? $model->getAdminName()
                : (new ReflectionClass($model))->getShortName();
        }

        return (string)$this->model_class;
    }

    public function getModelType(): ?string
    {
        $model = $this->getModelRecord();
        return $model instanceof TrailModelInterface ? $model->getAdminType() : null;
    }

    public function getModelRecord(): ?Model
    {
        if (empty($this->model_class)) {
            return null;
        }

        return TrailModelCollection::getModelByClassAndId($this->model_class, $this->model_id);
    }

    public function getDataModelRecord(): ?Model
    {
        if (empty($this->data['model_class'])) {
            return null;
        }

        return TrailModelCollection::getModelByClassAndId($this->data['model_class'], $this->data['model_id'] ?? null);
    }

    /**
     * The `message` attribute holds the stored pointer, this returns the text it renders to.
     */
    public function getMessage(): ?string
    {
        $message = Message::fromJson($this->message);
        return $message ? (string)$message : null;
    }

    public function isAuthPermissionType(): bool
    {
        return in_array($this->type, [static::TYPE_ASSIGN, static::TYPE_REVOKE], true);
    }

    public function isAuthPermissionAssignType(): bool
    {
        return $this->type === static::TYPE_ASSIGN;
    }

    public function isDeleteType(): bool
    {
        return in_array($this->type, [static::TYPE_DELETE, static::TYPE_CHILD_DELETE], true);
    }

    public function isCreateType(): bool
    {
        return $this->type === static::TYPE_CREATE;
    }

    public function isUpdateType(): bool
    {
        return $this->type === static::TYPE_UPDATE;
    }

    public function hasAttributesEnabled(): bool
    {
        return $this->isCreateType() || $this->isUpdateType();
    }

    public function hasDataModelEnabled(): bool
    {
        return $this->getType()?->hasDataModelEnabled() ?? false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createOrderTrail(?TrailModelInterface $model, ?Message $message = null, array $data = []): static
    {
        $trail = static::create();
        $trail->type = static::TYPE_ORDER;

        if ($model) {
            $trail->model_class = $model->getTrailBehavior()->modelClass;
            $trail->model_id = $model instanceof ActiveRecordInterface ? $model->getPrimaryKey(true) : null;
        }

        $trail->message = $message?->toJson();
        $trail->data = $data;
        $trail->insert();

        return $trail;
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function getAdminRouteByModel(?Model $model, int|string|null $id = null): array
    {
        if ($model instanceof TrailModelInterface) {
            if ($model instanceof ActiveRecord) {
                $id ??= implode('-', $model->getPrimaryKey(true));
            }

            $model = implode('@', array_filter([$model->getTrailBehavior()->modelClass, $id]));
        }

        return ['/admin/trail/index', 'model' => $model];
    }

    /**
     * A type's `message` is a {@see Message} pointer, not text: it is rendered in the language of whoever reads the
     * trail, by {@see TrailGridView}.
     *
     * @return list<TrailType>
     */
    public function getTypes(): array
    {
        return [
            TrailType::make(static::TYPE_DEFAULT)
                ->name(Yii::t('skeleton', 'TRAIL_MESSAGE'))
                ->icon('info-circle'),
            TrailType::make(static::TYPE_CREATE)
                ->name(Yii::t('skeleton', 'COMMON_CREATED'))
                ->parentType(static::TYPE_CHILD_CREATE)
                ->icon('plus'),
            TrailType::make(static::TYPE_UPDATE)
                ->name(Yii::t('skeleton', 'COMMON_UPDATED'))
                ->parentType(static::TYPE_CHILD_UPDATE)
                ->icon('pencil-alt'),
            TrailType::make(static::TYPE_DELETE)
                ->name(Yii::t('skeleton', 'COMMON_DELETED'))
                ->message(Message::make('skeleton', 'TRAIL_WAS_DELETED'))
                ->parentType(static::TYPE_CHILD_DELETE)
                ->icon('trash-alt'),
            TrailType::make(static::TYPE_ASSIGN)
                ->name(Yii::t('skeleton', 'TRAIL_PERMISSION_ASSIGNED'))
                ->icon('user-plus'),
            TrailType::make(static::TYPE_REVOKE)
                ->name(Yii::t('skeleton', 'TRAIL_PERMISSION_REVOKED'))
                ->icon('user-minus'),
            TrailType::make(static::TYPE_CHILD_CREATE)
                ->name(Yii::t('skeleton', 'COMMON_CREATED'))
                ->message(Message::make('skeleton', 'TRAIL_CREATED'))
                ->hasDataModel()
                ->icon('plus'),
            TrailType::make(static::TYPE_CHILD_UPDATE)
                ->name(Yii::t('skeleton', 'COMMON_UPDATED'))
                ->message(Message::make('skeleton', 'TRAIL_UPDATED'))
                ->hasDataModel()
                ->icon('pencil-alt'),
            TrailType::make(static::TYPE_CHILD_DELETE)
                ->name(Yii::t('skeleton', 'COMMON_DELETED'))
                ->message(Message::make('skeleton', 'TRAIL_DELETED'))
                ->hasDataModel()
                ->icon('trash-alt'),
            TrailType::make(static::TYPE_ORDER)
                ->name(Yii::t('skeleton', 'TRAIL_ORDERED'))
                ->icon('sort-amount-down'),
            TrailType::make(static::TYPE_PASSWORD)
                ->name(Yii::t('skeleton', 'TRAIL_PASSWORD_CHANGED'))
                ->message(Message::make('skeleton', 'TRAIL_THE_PASSWORD_WAS_CHANGED'))
                ->icon('key'),
        ];
    }

    #[Override]
    public static function getTypeClass(): string
    {
        return TrailType::class;
    }

    public function getType(): ?TrailType
    {
        /** @var TrailType|null */
        return static::findType(static::normalizeTypeValue($this->type ?? null));
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            'model_class' => Yii::t('skeleton', 'TRAIL_MODEL_LABEL'),
            'user_id' => Yii::t('skeleton', 'TRAIL_USER_ID_LABEL'),
            'data' => Yii::t('skeleton', 'TRAIL_DATA_LABEL'),
            'created_at' => Yii::t('skeleton', 'TRAIL_CREATED_AT_LABEL'),
        ];
    }

    #[Override]
    public function formName(): string
    {
        return 'Trail';
    }

    #[Override]
    public static function tableName(): string
    {
        return '{{%trail}}';
    }
}
