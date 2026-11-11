<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Html\Span;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Override;
use Stringable;
use Yii;

class RecentlyEditedPanel extends TrailPanel
{
    protected int $scanLimit = 200;

    #[Override]
    protected function configure(): void
    {
        $this->title ??= Yii::t('skeleton', 'DASHBOARD_RECENT_TITLE');
        $this->icon ??= 'user-pen';
        $this->url ??= $this->getUserTrailRoute();

        parent::configure();
    }

    /**
     * @return array<int|string, mixed>|null
     */
    protected function getUserTrailRoute(): ?array
    {
        $user = $this->webuser->getIdentity();

        return $user instanceof User && $this->webuser->can(Trail::AUTH_TRAIL_INDEX, ['user' => $user])
            ? ['/admin/user-trail/index', 'id' => $user->id]
            : null;
    }

    #[Override]
    protected function getBody(): string|Stringable
    {
        $userId = $this->webuser->getId();
        $classes = $this->getModelClasses();

        if ($userId === null || !$classes) {
            return '';
        }

        /** @var Trail[] $trails */
        $trails = Trail::find()
            ->where(['user_id' => $userId])
            ->andWhere(['not', ['model_class' => null]])
            ->orderBy(['id' => SORT_DESC])
            ->limit($this->scanLimit)
            ->all();

        $latest = [];

        foreach ($trails as $trail) {
            $key = $this->getTrailModelKey($trail);

            if (!array_key_exists($key, $latest) && $this->isListedClass($this->getTrailModelClass($trail), $classes)) {
                $latest[$key] = $trail->type === Trail::TYPE_DELETE ? null : $trail;
            }
        }

        $latest = array_values(array_filter($latest));
        $this->preloadTrailRecords($latest);

        $lines = [];

        foreach ($latest as $trail) {
            $record = $this->getTrailRecord($trail);

            if ($record) {
                $lines[] = $this->getLine(
                    Span::make()->text($record->getAdminName()),
                    [$record->getAdminType()],
                    $trail,
                    $this->getRecordUrl($record),
                );
            }

            if (count($lines) >= $this->limit) {
                break;
            }
        }

        return $this->getList($lines);
    }
}
