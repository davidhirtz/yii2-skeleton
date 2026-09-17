<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Breadcrumb;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;

/**
 * How a model presents itself in the admin — its link, the permission guarding it, its name, the noun it is filed
 * under, its icon and where it sits in the admin's nesting. Implemented via {@see AdminModelTrait}, which leaves
 * {@see static::getAdminRoute()} and {@see static::getPermissionName()} to the model.
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
     * The record this one is filed under in the admin, `null` for one at the top of its nav item.
     */
    public function getAdminParent(): ?AdminModelInterface;

    /**
     * The listing this record appears in — its parent's collection, or its nav item's index — as a breadcrumb,
     * `null` for a record whose listing is its parent's own page.
     */
    public function getAdminIndexBreadcrumb(): ?Breadcrumb;

    /**
     * How a record edited *through* another names itself under that record's title — its noun and its place in
     * the owner, never its own name. **`null` means the record owns its page**, which is what tells
     * {@see \Hirtz\Skeleton\Widgets\Navs\ModelHeader} where the H1 belongs.
     */
    public function getAdminSubtitle(): ?string;

    /**
     * How this model is named in the query string of a controller that is scoped to it, so a widget can build that
     * controller's routes without knowing which model it has.
     */
    public function getParamName(): string;

    /**
     * The permission that guards {@see static::getAdminRoute()}. A model only ever edited through another answers
     * that one's — there is no permission per model that has no page of its own.
     */
    public function getPermissionName(): string;
}
