<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\html\traits;

use Closure;
use Yii;

trait TagVisibilityTrait
{
    private array $roles = [];
    private bool $visible = true;

    public function roles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function visible(bool|Closure $visible): static
    {
        $this->visible = $visible instanceof Closure ? $visible() : $visible;
        return $this;
    }

    public function isVisible(): bool
    {
        if (!$this->visible) {
            return false;
        }

        foreach ($this->roles as $role) {
            if ('*' === $role || Yii::$app->getUser()->can($role)) {
                return true;
            }
        }

        return !$this->roles;
    }
}
