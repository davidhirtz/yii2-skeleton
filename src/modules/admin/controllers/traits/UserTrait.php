<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers\traits;

use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait UserTrait
{
    protected function findUserForm(int $id, ?string $permissionName = null): UserForm
    {
        if (!$user = UserForm::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['user' => $user])) {
            throw new ForbiddenHttpException();
        }

        return $user;
    }
}
