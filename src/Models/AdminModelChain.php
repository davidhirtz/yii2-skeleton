<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models;

use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;

/**
 * A record named the way its page header names it: the **base** record — the one that owns the page — and the
 * subtitle of every record between it and this one, "About — Section 3/5 · Asset 1/2". Which record is the base
 * is {@see AdminModelInterface::getAdminSubtitle()}: a record that answers `null` owns its page.
 */
final readonly class AdminModelChain
{
    /**
     * @param list<AdminModelInterface> $models everything between the base and the record, the record last
     * @param list<string> $subtitles each model's subtitle, in the same order
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
