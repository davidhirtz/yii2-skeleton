<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Web\Traits;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\Interfaces\StatusAttributeInterface;
use Hirtz\Skeleton\Web\Controller;
use Yii;
use yii\base\Module;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The body of the status action the grid's status icon posts to, without any opinion on who may run it — a
 * controller resolves and authorises the record the way its update action does and calls this.
 *
 * @mixin Controller<Module>
 */
trait StatusControllerTrait
{
    protected function updateStatus(ActiveRecord&AdminModelInterface&StatusAttributeInterface $model): Response
    {
        // What the grid does not offer, the route refuses: a record the model itself exempts, and one whose stored
        // status the configuration no longer declares, are both left alone.
        $status = $model->isStatusUpdatable() ? $model->getNextStatus() : null;

        if (!$status) {
            throw new NotFoundHttpException();
        }

        $model->setAttribute('status', $status->value);

        if ($model->update()) {
            $this->success(Yii::t('skeleton', 'COMMON_STATUS_SUCCESS_UPDATED', [
                'name' => $model->getAdminName(),
                'status' => $status->getName(),
            ]));
        }

        $this->error($model);

        return $this->redirect($this->request->getReferrer() ?? ['index']);
    }
}
