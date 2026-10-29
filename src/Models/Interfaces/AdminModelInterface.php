<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Breadcrumb;

interface AdminModelInterface
{
    /**
     * @return array<array-key, mixed>|false
     */
    public function getAdminRoute(): array|false;

    public function getAdminName(): string;

    /**
     * The type name of a model that has types, the model's own noun otherwise.
     */
    public function getAdminType(): string;

    public function getAdminIcon(): ?string;

    /**
     * `null` for a record at the top of its nav item.
     */
    public function getAdminParent(): ?AdminModelInterface;

    /**
     * `null` for a record whose listing is its parent's own page.
     */
    public function getAdminIndexBreadcrumb(): ?Breadcrumb;

    /**
     * `null` means the record owns its page, which tells {@see \Hirtz\Skeleton\Widgets\Navs\ModelHeader} where the
     * H1 belongs.
     */
    public function getAdminSubtitle(): ?string;

    public function getParamName(): string;

    /**
     * A model only ever edited through another answers that one's.
     */
    public function getPermissionName(): string;
}
