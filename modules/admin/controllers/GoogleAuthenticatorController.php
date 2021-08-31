<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\forms\GoogleAuthenticatorForm;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * Class GoogleAuthenticatorController
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class GoogleAuthenticatorController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create', 'delete'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return Response
     */
    public function actionCreate()
    {
        $form = new GoogleAuthenticatorForm([
            'user' => Yii::$app->getUser()->getIdentity(),
        ]);

        if ($form->load(Yii::$app->getRequest()->post())) {
            if ($form->save()) {
                $this->success(Yii::t('skeleton', 'Google Authenticator is now activate on your account.'));
            } else {
                $this->error($form->getFirstErrors());
            }
        }

        return $this->redirect(['account/update']);
    }
}