<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\models\forms;

use davidhirtz\yii2\skeleton\base\traits\ModelTrait;
use davidhirtz\yii2\skeleton\models\User;
use Override;
use Yii;
use yii\base\Model;

class OwnershipForm extends Model
{
    use ModelTrait;

    public ?string $name = null;
    private ?User $user = null;

    #[Override]
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

    protected function validateUser(): void
    {
        $this->user ??= User::find()
            ->andWhereName($this->name)
            ->limit(1)
            ->one();

        if (!$this->user) {
            $this->addError('name', Yii::t('skeleton', 'The user {user} was not found.', [
                'user' => $this->name,
            ]));
        }

        if ($this->user?->isDisabled()) {
            $this->addError('name', Yii::t('skeleton', 'This user is currently disabled and thus can not be made website owner!'));
        }

        if ($this->user?->isOwner()) {
            $this->addError('name', Yii::t('skeleton', 'This user is already the owner of the website!'));
        }
    }

    public function update(): bool
    {
        if ($this->validate()) {
            $owners = User::find()
                ->andWhere(['is_owner' => true])
                ->all();

            foreach ($owners as $owner) {
                $owner->is_owner = false;
                $owner->update();
            }

            if ($this->user) {
                $this->user->is_owner = true;
                return (bool)$this->user->update(false);
            }
        }

        return false;
    }

    #[Override]
    public function attributeLabels(): array
    {
        return [
            'name' => Yii::t('skeleton', 'Username'),
        ];
    }
}
