<?php

namespace davidhirtz\yii2\skeleton\models;

use davidhirtz\yii2\skeleton\models\queries\AuthItemQuery;
use Yii;
use yii\rbac\Item;

/**
 * Class AuthItem.
 * @package davidhirtz\yii2\skeleton\models
 *
 * @property string $name
 * @property integer $type
 * @property string $description
 * @property string $rule_name
 * @property string $data
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User[] $users
 */
class AuthItem extends \davidhirtz\yii2\skeleton\db\ActiveRecord
{

    /**
     * @var bool
     */
    public $isAssigned = false;

    /**
     * @var bool
     */
    public $isInherited = false;

    /**
     * @see \davidhirtz\yii2\skeleton\models\queries\AuthItemQuery::allWithChildren()
     * @var \davidhirtz\yii2\skeleton\models\AuthItem[]
     */
    public $children = [];

    /***********************************************************************
     * Relations.
     ***********************************************************************/

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable(Yii::$app->authManager->assignmentTable, ['item_name' => 'name']);
    }

    /***********************************************************************
     * Methods.
     ***********************************************************************/

    /**
     * @return AuthItemQuery
     */
    public static function find()
    {
        return new AuthItemQuery(get_called_class());
    }

    /***********************************************************************
     * Getters / setters.
     ***********************************************************************/

    /**
     * @return bool
     */
    public function hasPermission()
    {
        foreach ($this->children as $authItem) {
            if ($authItem->isAssigned) {
                return true;
            }
        }

        return $this->isAssigned;
    }

    /**
     * @return string the display name.
     */
    public function getDisplayName()
    {
        return str_replace(' ', ' / ', $this->generateAttributeLabel($this->name));
    }

    /**
     * @return string
     */
    public function getTypeIcon()
    {
        switch ($this->type) {
            case Item::TYPE_ROLE:
                return 'user';

            case Item::TYPE_PERMISSION:
                return 'edit';
        }

        return null;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        switch ($this->type) {
            case Item::TYPE_ROLE:
                return 'Role';

            case Item::TYPE_PERMISSION:
                return 'Permission';
        }

        return null;
    }

    /**
     * @return bool
     */
    public function getIsRole()
    {
        return $this->type == Item::TYPE_ROLE;
    }

    /**
     * @return bool
     */
    public function getIsPermission()
    {
        return $this->type == Item::TYPE_PERMISSION;
    }

    /***********************************************************************
     * Active Record.
     ***********************************************************************/

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('app', 'Name'),
            'displayName' => Yii::t('app', 'Name'),
            'type' => Yii::t('app', 'Type'),
            'description' => Yii::t('app', 'Description'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auth_item}}';
    }
}
