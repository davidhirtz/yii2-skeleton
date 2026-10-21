<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Traits;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Widget;

/**
 * @mixin Widget
 */
trait UserRouteTrait
{
    /**
     * The first page of a user the acting one may open: the account needs the user permission alone, since it renders
     * a user they may not manage read-only.
     *
     * @return array<int|string, mixed>|null
     */
    protected function getUserRoute(User $user): ?array
    {
        return match (true) {
            $this->webuser->can(User::AUTH_USER) => $user->getAdminRoute(),
            $this->webuser->can(User::AUTH_USER_ASSIGN, ['user' => $user]) => ['/admin/user-auth/index', 'id' => $user->id],
            $this->webuser->can(Trail::AUTH_TRAIL_INDEX, ['user' => $user]) => ['/admin/user-trail/index', 'id' => $user->id],
            default => null,
        };
    }
}
