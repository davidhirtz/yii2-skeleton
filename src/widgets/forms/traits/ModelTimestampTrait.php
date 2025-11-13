<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\traits;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\timeago\Timeago;
use Yii;

trait ModelTimestampTrait
{
    public function renderFooter(): void
    {
        if ($items = array_filter($this->getFooterItems())) {
            echo $this->listRow($items);
        }
    }

    protected function getFooterItems(): array
    {
        return $this->getTimestampItems();
    }

    protected function getTimestampItems(): array
    {
        if ($this->model->getIsNewRecord()) {
            return [];
        }

        if ($this->model->updated ?? null) {
            $text = Yii::t('skeleton', 'Last updated by {user} {timestamp}', [
                'timestamp' => Timeago::tag($this->model->updated_at),
                'user' => Html::username($this->model->updated),
            ]);
        } else {
            $text = Yii::t('skeleton', 'Last updated {timestamp}', [
                'timestamp' => Timeago::tag($this->model->updated_at),
            ]);
        }

        // @phpstan-ignore-next-line
        $hasTrailBehavior = $this->model instanceof TrailModelInterface
            && Yii::$app->getUser()->can('trailIndex');

        return [
            $hasTrailBehavior ? Html::a($text, Trail::getAdminRouteByModel($this->model)) : $text,
            Yii::t('skeleton', 'Created {timestamp}', [
                'timestamp' => Timeago::tag($this->model->created_at),
            ]),
        ];
    }
}
