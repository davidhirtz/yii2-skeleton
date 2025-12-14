<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Models\User;

trait UserWidgetTrait
{
    protected ?User $user = null;

    public function user(User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
