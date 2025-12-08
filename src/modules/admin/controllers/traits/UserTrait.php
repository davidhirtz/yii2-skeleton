<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers\Traits;

use Hirtz\Skeleton\Models\User;
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
