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
 * The team's last changes: original rows only, since each parent row repeats one of them.
 */
class ActivityPanel extends TrailPanel
{
    protected int $limit = 10;
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
        $this->footerLabel ??= Yii::t('skeleton', 'DASHBOARD_ACTIVITY_SHOW_ALL_LINK');
        $this->footerUrl ??= ['/admin/trail/index'];

        parent::configure();
    }

    #[Override]
    protected function getBody(): string|Stringable
    {
        /** @var Trail[] $trails */
        $trails = Trail::find()
            ->where(['type' => [Trail::TYPE_CREATE, Trail::TYPE_UPDATE, Trail::TYPE_DELETE, Trail::TYPE_ORDER]])
            ->andWhere(['not', ['model_class' => null]])
            ->with('user')
            ->orderBy(['id' => SORT_DESC])
            ->limit($this->scanLimit)
            ->all();

        $trails = array_values(array_filter(
            $trails,
            fn (Trail $trail): bool => !is_a($this->getTrailModelClass($trail), User::class, true)
        ));

        /** @var list<array{trail: Trail, count: int}> $groups */
        $groups = [];

        foreach ($trails as $trail) {
            $last = $groups ? $groups[array_key_last($groups)] : null;

            if ($last && $this->isRepeat($last['trail'], $trail)) {
                $groups[array_key_last($groups)]['count']++;
                continue;
            }

            if (count($groups) >= $this->limit) {
                break;
            }

            $groups[] = ['trail' => $trail, 'count' => 1];
        }

        $this->preloadTrailRecords(array_column($groups, 'trail'));

        $lines = [];

        foreach ($groups as $group) {
            $lines[] = $this->getActivityLine($group['trail'], $group['count']);
        }

        return $this->getList($lines);
    }

    /**
     * A record created and saved again reads as one line, a deletion as its own.
     */
    protected function isRepeat(Trail $newer, Trail $older): bool
    {
        return $newer->user_id === $older->user_id
            && $newer->type !== Trail::TYPE_DELETE
            && $older->type !== Trail::TYPE_DELETE
            && $this->getTrailModelKey($newer) === $this->getTrailModelKey($older)
            && $newer->created_at->getTimestamp() - $older->created_at->getTimestamp() <= $this->collapseInterval;
    }

    protected function getActivityLine(Trail $trail, int $count): Stringable
    {
        $record = $this->getTrailRecord($trail);

        $title = $record
            ? $this->getRecordLink($record)
            : Span::make()->class('text-muted')->text($trail->getModelName());

        $parent = $record?->getAdminParent();

        return $this->getLine($title, [
            $trail->getType()?->getName(),
            $count > 1 ? Yii::t('skeleton', 'DASHBOARD_ACTIVITY_COUNT_TEXT', ['count' => $count]) : null,
            $parent?->getAdminName(),
            $trail->user?->getUsername(),
        ], $trail);
    }
}
