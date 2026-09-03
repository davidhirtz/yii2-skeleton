<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\User;
use Override;
use Yii;
use yii\base\Model;

class OwnershipForm extends Model
{
    use ModelTrait;

    public function __construct(public readonly User $user)
    {
        parent::__construct();
    }

    #[Override]
    public function beforeValidate(): bool
    {
        if ($this->user->isDisabled()) {
            $this->addError('name', Lang::t('skeleton', 'OWNERSHIP_USER_CURRENTLY_DISABLED'));
        }

        if ($this->user->isOwner()) {
            $this->addError('name', Lang::t('skeleton', 'OWNERSHIP_USER_ALREADY_OWNER'));
        }

        return parent::beforeValidate();
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

            $this->user->is_owner = true;
            return (bool)$this->user->update(false);
        }

        return false;
    }
}
