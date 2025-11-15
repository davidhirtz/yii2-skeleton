<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\traits;

use davidhirtz\yii2\skeleton\models\User;

trait UserWidgetTrait
{
    protected ?User $user = null;

    public function user(User $user): static
    {
        $this->user = $user;
        return $this;
    }
}