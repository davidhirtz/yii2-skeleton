<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Forms;

use Hirtz\Skeleton\Base\Traits\ModelTrait;
use Hirtz\Skeleton\Models\Forms\Traits\UserFormTrait;
use Hirtz\Skeleton\Models\User;
use yii\base\Model;

class AccountUpdateForm extends Model
{
    use ModelTrait;
    use UserFormTrait;

    public function __construct(public User $user, array $config = [])
    {
        parent::__construct($config);
    }

    protected function getUserAttributeNames(): array
    {
        return [
            'name',
            'language',
            'timezone',
            ...$this->user->getCustomAttributeNames(),
        ];
    }
}
