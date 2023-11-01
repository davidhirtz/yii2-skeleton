<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\db\TypeAttributeTrait;
use davidhirtz\yii2\skeleton\models\queries\AuthItemQuery;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use Yii;
use yii\rbac\Item;

/**
 * @property string $name
 * @property int $type
 * @property string $description
 * @property string $rule_name
 * @property string $data
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User[] $users {@see AuthItem::getUsers()}
 */
class AuthItem extends ActiveRecord
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable(Yii::$app->getAuthManager()->assignmentTable, ['item_name' => 'name']);
    }

    public static function find(): AuthItemQuery
    {
        return Yii::createObject(AuthItemQuery::class, [static::class]);
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return str_replace(' ', ' / ', $this->generateAttributeLabel($this->name));
    }

    /**
     * @return string
     */
    public function getTypeIcon(): string
    {
        return $this->getTypeOptions()['icon'] ?? '';
    }

    /** @noinspection PhpUnused */
    public function hasPermission(): bool
    {
        foreach ($this->children as $authItem) {
            if ($authItem->isAssigned) {
                return true;
            }
        }

        return $this->isAssigned;
    }

    /**
     * @return bool
     */
    public function isRole(): bool
    {
        return $this->type == Item::TYPE_ROLE;
    }

    /**
     * @return bool
     */
    public function isPermission(): bool
    {
        return $this->type == Item::TYPE_PERMISSION;
    }

    /**
     * @return array
     */
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

    /**
     * @inheritDoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('skeleton', 'Permission'),
            'type' => Yii::t('skeleton', 'Type'),
            'description' => Yii::t('skeleton', 'Description'),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%auth_item}}';
    }
}
