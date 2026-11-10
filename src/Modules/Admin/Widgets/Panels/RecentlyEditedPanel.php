<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Panels;

use Hirtz\Skeleton\Models\Trail;
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
        parent::configure();
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
                $lines[] = $this->getLine($this->getRecordLink($record), [$record->getAdminType()], $trail);
            }

            if (count($lines) >= $this->limit) {
                break;
            }
        }

        return $this->getList($lines);
    }
}
