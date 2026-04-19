<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

use Hirtz\Skeleton\Models\Breadcrumb;

trait BreadcrumbTrait
{
    /**
     * @var Breadcrumb[]|null
     */
    protected ?array $breadcrumbs = null;

    /**
     * @param Breadcrumb[]|null $breadcrumbs
     */
    public function breadcrumbs(?array $breadcrumbs): static
    {
        $this->breadcrumbs = $breadcrumbs;
        return $this;
    }

    /**
     * @param Breadcrumb[] $breadcrumbs
     */
    public function addBreadcrumbs(array $breadcrumbs): static
    {
        $this->breadcrumbs = $this->breadcrumbs ? [...$this->breadcrumbs, ...$breadcrumbs] : $breadcrumbs;
        return $this;
    }

    public function addBreadcrumb(Breadcrumb|string|null $label, array|string|null $url = null): static
    {
        if ($label) {
            $this->breadcrumbs[] = $label instanceof Breadcrumb ? $label : new Breadcrumb($label, $url);
        }

        return $this;
    }

    /**
     * @return Breadcrumb[]
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs ?? [];
    }
}
