<?php

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\base\Model;

class OwnershipForm extends Model
{
    public ?string $name = null;

    private ?User $_user = null;

    public function rules(): array
    {
        return [
            [
                ['name'],
                'trim',
            ],
            [
                ['name'],
                'required',
            ],
            [
                ['name'],
                $this->validateUser(...),
            ],
        ];
    }

    public function validateUser(): bool
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addError('name', Yii::t('skeleton', 'The user {name} was not found.', ['name' => $this->name]));
        } elseif ($user->isDisabled()) {
            $this->addError('name', Yii::t('skeleton', 'This user is currently disabled and thus can not be made website owner!'));
        } elseif ($user->isOwner()) {
            $this->addError('name', Yii::t('skeleton', 'This user is already the owner of the website!'));
        }

        return !$this->hasErrors();
    }

    /**
     * Transfers the website ownership to user.
     */
    public function transfer(): bool|int
    {
        if ($this->validate()) {
            User::updateAll(['is_owner' => false, 'updated_at' => new DateTime()], ['is_owner' => true]);

            $user = $this->getUser();
            $user->is_owner = true;

            return $user->update(false);
        }

        return false;
    }

    public function getUser(): ?User
    {
        if ($this->_user === null) {
            $this->_user = User::find()
                ->select(['id', 'status', 'name', 'is_owner', 'updated_at'])
                ->andWhereName($this->name)
                ->limit(1)
                ->one();
        }

        return $this->_user;
    }

    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('skeleton', 'Username'),
        ];
    }
}
