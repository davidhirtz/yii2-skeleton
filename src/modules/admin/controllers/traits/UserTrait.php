<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers\traits;

use davidhirtz\yii2\skeleton\models\User;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait UserTrait
{
    protected function findUser(int $id, ?string $permissionName = null): User
    {
        if (!$user = User::findOne($id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['user' => $user])) {
            throw new ForbiddenHttpException();
        }

        return $user;
    }
}
