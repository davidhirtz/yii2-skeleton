<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;

final readonly class AdminModelChain
{
    /**
     * Both lists run from the base record to this one, the record last.
     *
     * @param list<AdminModelInterface> $models
     * @param list<string> $subtitles
     */
    private function __construct(
        public AdminModelInterface $base,
        public array $models,
        public array $subtitles,
    ) {
    }

    /**
     * None of the shipped models can form a cycle, but `getAdminParent()` is a project extension point.
     */
    public static function fromModel(AdminModelInterface $model, int $maxCount = 16): self
    {
        $models = [];
        $subtitles = [];

        while (($subtitle = $model->getAdminSubtitle()) !== null && count($models) < $maxCount) {
            $models[] = $model;
            $subtitles[] = $subtitle;
            $parent = $model->getAdminParent();

            if (!$parent) {
                break;
            }

            $model = $parent;
        }

        return new self($model, array_reverse($models), array_reverse($subtitles));
    }
}
