<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Traits;

trait BreadcrumbTrait
{
    /**
     * @var array<int, array{label: string, url: array|string|null}>
     */
    protected ?array $breadcrumbs = null;

    /**
     * @param array<int, array{label: string, url: array|string|null}>|array<string,array|string|null> $breadcrumbs
     */
    public function breadcrumbs(?array $breadcrumbs): static
    {
        $this->breadcrumbs = null;
        return $breadcrumbs ? $this->addBreadcrumbs($breadcrumbs) : $this;
    }

    /**
     * @param array<int, array{label: string, url: array|string|null}>|array<string,array|string|null> $breadcrumbs
     */
    public function addBreadcrumbs(array $breadcrumbs): static
    {
        foreach ($breadcrumbs as $key => $value) {
            $this->addBreadcrumb(is_int($key) ? $value : $key, is_string($key) ? $value : null);
        }

        return $this;
    }

    public function addBreadcrumb(?string $label, array|string|null $url = null): static
    {
        if ($label) {
            $this->breadcrumbs[] = ['label' => $label, 'url' => $url];
        }

        return $this;
    }

    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs ?? [];
    }
}
