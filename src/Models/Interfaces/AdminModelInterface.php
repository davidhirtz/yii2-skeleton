<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Traits\AdminModelTrait;

/**
 * How a model presents itself in the admin — its link, its name, the noun it is filed under and its icon. Implemented
 * via {@see AdminModelTrait}, which leaves only {@see static::getAdminRoute()} to the model.
 */
interface AdminModelInterface
{
    /**
     * @return array<array-key, mixed>|false `false` for a model that has no admin page of its own
     */
    public function getAdminRoute(): array|false;

    public function getAdminName(): string;

    /**
     * The type name of a model that has types, the model's own noun otherwise.
     */
    public function getAdminType(): string;

    public function getAdminIcon(): ?string;

    /**
     * How this model is named in the query string of a controller that is scoped to it, so a widget can build that
     * controller's routes without knowing which model it has.
     */
    public function getParamName(): string;
}
