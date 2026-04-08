<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Closure;
use Yii;

trait VisibilityTrait
{
    protected ?array $roles = null;
    protected Closure|bool $visible = true;

    public function roles(array $roles): static
    {
        $this->roles = $this->roles ? [...$this->roles, ...$roles] : $roles;
        return $this;
    }

    /**
     * @param Closure(self):(bool)|bool $visible
     * @return $this
     */
    public function visible(Closure|bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function isVisible(): bool
    {
        $visible = $this->visible instanceof Closure ? ($this->visible)($this) : $this->visible;

        if (!$visible) {
            return false;
        }

        if ($this->roles === null) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role === '*' || Yii::$app->getUser()->can($role)) {
                return true;
            }
        }

        return false;
    }
}
