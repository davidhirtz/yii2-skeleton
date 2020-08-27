<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\timeago\Timeago;
use Yii;

/**
 * Class ModelTimestampTrait
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\forms
 */
trait ModelTimestampTrait{

    /**
     * @return array
     */
    protected function getTimestampItems(): array
    {
        $items = [];

        if (!$this->model->getIsNewRecord()) {
            if ($this->model->updated_by_user_id) {
                $items[] = Yii::t('skeleton', 'Last updated by {user} {timestamp}', [
                    'timestamp' => Timeago::tag($this->model->updated_at),
                    'user' => Html::username($this->model->updated, Yii::$app->getUser()->can('userUpdate', ['user' => $this->model->updated]) ? ['/admin/user/update', 'id' => $this->model->updated_by_user_id] : null),
                ]);
            } else {
                $items[] = Yii::t('skeleton', 'Last updated {timestamp}', [
                    'timestamp' => Timeago::tag($this->model->updated_at),
                ]);
            }

            $items[] = Yii::t('skeleton', 'Created {timestamp}', [
                'timestamp' => Timeago::tag($this->model->created_at),
            ]);
        }

        return $items;
    }
}