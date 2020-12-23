<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers\traits;

use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Trait UserTrait
 * @package davidhirtz\yii2\cms\modules\admin\controllers\traits
 */
trait UserTrait
{
    /**
     * @param int $id
     * @param string|null $permissionName
     * @return UserForm
     */
    protected function findUserForm($id, $permissionName = null)
    {
        if (!$user = UserForm::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if ($permissionName && !Yii::$app->getUser()->can($permissionName, ['entry' => $user])) {
            throw new ForbiddenHttpException();
        }

        return $user;
    }
}