<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Closure;
use Hirtz\Skeleton\Models\User;

trait VisibilityTrait
{
    /**
     * @var list<string>|null
     */
    protected ?array $roles = null;
    protected Closure|bool $visible = true;

    /**
     * @param list<string>|null $roles
     */
    public function roles(?array $roles): static
    {
        $this->roles = $roles
            ? array_values(array_unique(array_filter($this->roles ? [...$this->roles, ...$roles] : $roles)))
            : null;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles ?? [];
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
            $granted = match ($role) {
                User::ROLE_ANY => true,
                User::ROLE_AUTHENTICATED => !$this->webuser->getIsGuest(),
                default => $this->webuser->can($role),
            };

            if ($granted) {
                return true;
            }
        }

        return false;
    }
}
