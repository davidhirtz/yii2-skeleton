<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Override;
use Stringable;
use Yii;

/**
 * The others' last changes: original rows only, since each parent row repeats one of them.
 */
class ActivityPanel extends TrailPanel
{
    protected int $scanLimit = 100;

    /**
     * Saves of one record by one user this close together read as one line.
     */
    protected int $collapseInterval = 600;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->roles ??= [Trail::AUTH_TRAIL_INDEX];
        parent::__construct($config);
    }

    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'DASHBOARD_ACTIVITY_TITLE');
        $this->icon ??= 'users';
        $this->url ??= ['/admin/trail/index'];

        parent::configure();
    }

    #[Override]
    protected function getBody(): string|Stringable
    {
        $query = Trail::find()
            ->where(['type' => [Trail::TYPE_CREATE, Trail::TYPE_UPDATE, Trail::TYPE_DELETE, Trail::TYPE_ORDER]])
            ->andWhere(['not', ['model_class' => null]]);

        $userId = $this->webuser->getId();

        if ($userId !== null) {
            $query->andWhere(['or', ['user_id' => null], ['!=', 'user_id', $userId]]);
        }

        /** @var Trail[] $trails */
        $trails = $query
            ->with('user')
            ->orderBy(['id' => SORT_DESC])
            ->limit($this->scanLimit)
            ->all();

        $trails = array_values(array_filter(
            $trails,
            fn (Trail $trail): bool => !is_a($this->getTrailModelClass($trail), User::class, true)
        ));

        $copies = $this->findListedParentCopies($trails);

        /** @var list<array{trail: Trail, target: Trail, count: int}> $groups */
        $groups = [];

        foreach ($trails as $trail) {
            $target = $copies[$trail->id] ?? $trail;
            $last = $groups ? $groups[array_key_last($groups)] : null;

            if ($last && $this->isRepeat($last['trail'], $last['target'], $trail, $target)) {
                $groups[array_key_last($groups)]['count']++;
                continue;
            }

            if (count($groups) >= $this->limit) {
                break;
            }

            $groups[] = ['trail' => $trail, 'target' => $target, 'count' => 1];
        }

        $this->preloadTrailRecords([...array_column($groups, 'trail'), ...array_column($groups, 'target')]);

        $lines = [];

        foreach ($groups as $group) {
            $lines[] = $this->getActivityLine($group['trail'], $group['target'], $group['count']);
        }

        return $this->getList($lines);
    }

    /**
     * A change to a record inside a listed one (a section asset inside an entry) is shown on the listed record,
     * through the parent row the trail wrote for it.
     *
     * @param Trail[] $trails
     * @return array<int, Trail> the parent rows by the id of the row they repeat
     */
    protected function findListedParentCopies(array $trails): array
    {
        $classes = $this->getModelClasses();
        $ids = [];

        foreach ($trails as $trail) {
            if (!$this->isListedClass($this->getTrailModelClass($trail), $classes)) {
                $ids[] = $trail->id;
            }
        }

        if (!$classes || !$ids) {
            return [];
        }

        $copies = Trail::find()
            ->where(['type' => [Trail::TYPE_CHILD_CREATE, Trail::TYPE_CHILD_UPDATE, Trail::TYPE_CHILD_DELETE]])
            ->andWhere(['>', 'id', min($ids)])
            ->andWhere("JSON_EXTRACT([[data]], '$.trail_id') IN (" . implode(',', $ids) . ')')
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $listed = [];

        foreach ($copies as $copy) {
            $id = $copy->data['trail_id'] ?? null;

            if (is_int($id) && !isset($listed[$id]) && $this->isListedClass($this->getTrailModelClass($copy), $classes)) {
                $listed[$id] = $copy;
            }
        }

        return $listed;
    }

    /**
     * A record created and saved again reads as one line, its deletion as its own.
     */
    protected function isRepeat(Trail $newer, Trail $newerTarget, Trail $older, Trail $olderTarget): bool
    {
        return $newer->user_id === $older->user_id
            && !($newer === $newerTarget && $newer->type === Trail::TYPE_DELETE)
            && !($older === $olderTarget && $older->type === Trail::TYPE_DELETE)
            && $this->getTrailModelKey($newerTarget) === $this->getTrailModelKey($olderTarget)
            && $newer->created_at->getTimestamp() - $older->created_at->getTimestamp() <= $this->collapseInterval;
    }

    protected function getActivityLine(Trail $trail, Trail $target, int $count): Stringable
    {
        $record = $this->getTrailRecord($target);

        $title = $record
            ? Span::make()->text($record->getAdminName())
            : Span::make()->class('text-muted')->text($target->getModelName());

        return $this->getLine($title, [
            $trail === $target ? $trail->getType()?->getName() : $this->getChildText($trail),
            $count > 1 ? Yii::t('skeleton', 'DASHBOARD_ACTIVITY_COUNT_TEXT', ['count' => $count]) : null,
            $trail === $target ? $record?->getAdminParent()?->getAdminName() : null,
            $trail->user?->getUsername(),
        ], $trail, $record ? $this->getRecordUrl($record) : null);
    }

    protected function getChildText(Trail $trail): ?string
    {
        $params = ['model' => $trail->getModelName()];

        return match ($trail->type) {
            Trail::TYPE_CREATE => Yii::t('skeleton', 'TRAIL_CHILD_CREATE_TEXT', $params),
            Trail::TYPE_UPDATE => Yii::t('skeleton', 'TRAIL_CHILD_UPDATE_TEXT', $params),
            Trail::TYPE_DELETE => Yii::t('skeleton', 'TRAIL_CHILD_DELETE_TEXT', $params),
            default => $trail->getType()?->getName(),
        };
    }
}
