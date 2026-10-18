<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers\Traits;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Web\User as WebUser;
use yii\helpers\Console;

/**
 * The controller lists `user` in `options()` and calls `setUserIdentity()` in `beforeAction()`.
 */
trait UserIdentityTrait
{
    /**
     * @var string|null the email address of the user the changes are attributed to, the owner when it is not given
     */
    public ?string $user = null;

    protected function setUserIdentity(): bool
    {
        $query = User::find()->limit(1);

        $identity = $this->user !== null
            ? $query->andWhereEmail($this->user)->one()
            : $query->andWhere(['is_owner' => true])->one();

        if ($this->user !== null && !$identity) {
            $this->stderr("No user found for $this->user." . PHP_EOL, Console::FG_RED);
            return false;
        }

        WebUser::setSessionlessIdentity($identity);
        return true;
    }
}
