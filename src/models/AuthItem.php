<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\models;

use Hirtz\Skeleton\db\ActiveRecord;
use Hirtz\Skeleton\models\interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\models\queries\AuthItemQuery;
use Hirtz\Skeleton\models\queries\UserQuery;
use Hirtz\Skeleton\models\traits\TypeAttributeTrait;
use Yii;
use yii\rbac\Item;

/**
 * @property string $name
 * @property int $type
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

    public function getUsers(): UserQuery
    {
        /** @var UserQuery $query */
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

    public function getTypeIcon(): string
    {
        return $this->getTypeOptions()['icon'] ?? '';
    }

    /**
     * @noinspection PhpUnused
     */
    public function hasPermission(): bool
    {
        foreach ($this->children as $authItem) {
            if ($authItem->isAssigned) {
                return true;
            }
        }

        return $this->isAssigned;
    }

    public function isRole(): bool
    {
        return $this->type === Item::TYPE_ROLE;
    }

    public function isPermission(): bool
    {
        return $this->type === Item::TYPE_PERMISSION;
    }

    public static function getTypes(): array
    {
        return [
            Item::TYPE_ROLE => [
                'name' => 'Role',
                'icon' => 'user',
            ],
            Item::TYPE_PERMISSION => [
                'name' => 'Permission',
                'icon' => 'edit',
            ],
        ];
    }

    #[\Override]
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('skeleton', 'Permission'),
            'type' => Yii::t('skeleton', 'Type'),
            'description' => Yii::t('skeleton', 'Description'),
        ];
    }

    #[\Override]
    public static function tableName(): string
    {
        return '{{%auth_item}}';
    }
}
