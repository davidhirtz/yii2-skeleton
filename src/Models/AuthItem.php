<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\I18n\Message;
use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\Queries\AuthItemQuery;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Traits\TypeAttributeTrait;
use Hirtz\Skeleton\Models\Types\Type;
use Yii;
use yii\rbac\Item;

/**
 * @property string $name
 * @property string|null $description
 * @property string|null $rule_name
 * @property string|null $data
 * @property int|null $updated_at
 * @property int $created_at
 *
 * @property User[] $users {@see AuthItem::getUsers()}
 */
class AuthItem extends ActiveRecord implements TypeAttributeInterface
{
    use TypeAttributeTrait;

    /**
     * @var bool whether the item is assigned to the current user.
     */
    public ?bool $isAssigned = null;

    /**
     * @var bool whether the item is inherited by another item.
     */
    public ?bool $isInherited = null;

    /**
     * @see AuthItemQuery::allWithChildren
     * @var AuthItem[]
     */
    public array $children = [];

    /**
     * @return UserQuery<User>
     */
    public function getUsers(): UserQuery
    {
        /** @var UserQuery<User> $query */
        $query = $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable(Yii::$app->getAuthManager()->assignmentTable, ['item_name' => 'name']);

        return $query;
    }

    #[\Override]
    public static function find(): AuthItemQuery
    {
        return Yii::createObject(AuthItemQuery::class, [static::class]);
    }

    public function getDisplayName(): string
    {
        return str_replace(' ', ' / ', $this->generateAttributeLabel($this->name));
    }

    /**
     * The description is a {@see Message} pointer; a row written before 3.0 holds rendered English and is shown as
     * it stands.
     */
    public function getLabel(): string
    {
        return (string)(Message::fromJson($this->description) ?? $this->getDisplayName());
    }

    public function isRole(): bool
    {
        return $this->type === Item::TYPE_ROLE;
    }

    public function isPermission(): bool
    {
        return $this->type === Item::TYPE_PERMISSION;
    }

    /**
     * @return list<Type>
     */
    public function getTypes(): array
    {
        return [
            Type::make(Item::TYPE_ROLE)
                ->name(Yii::t('skeleton', 'AUTH_ITEM_TYPE_ROLE'))
                ->icon('user'),
            Type::make(Item::TYPE_PERMISSION)
                ->name(Yii::t('skeleton', 'AUTH_ITEM_TYPE_PERMISSION'))
                ->icon('edit'),
        ];
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('skeleton', 'AUTH_ITEM_NAME_LABEL'),
            'type' => Yii::t('skeleton', 'AUTH_ITEM_TYPE_LABEL'),
            'description' => Yii::t('skeleton', 'AUTH_ITEM_DESCRIPTION_LABEL'),
        ];
    }

    #[\Override]
    public static function tableName(): string
    {
        return '{{%auth_item}}';
    }
}
