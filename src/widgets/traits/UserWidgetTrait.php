<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\traits;

use Hirtz\Skeleton\models\User;

trait UserWidgetTrait
{
    protected ?User $user = null;

    public function user(User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
