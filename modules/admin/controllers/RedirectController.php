<?php

namespace davidhirtz\yii2\skeleton\modules\admin\controllers;

use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\web\Controller;
use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class RedirectController
 * @package davidhirtz\yii2\skeleton\modules\admin\controllers
 */
class RedirectController extends Controller
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
                        'actions' => ['create', 'delete', 'delete-all', 'index', 'update'],
                        'roles' => ['redirectCreate'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param int $user
     * @param string $q
     * @return string
     */
    public function actionIndex($user = null, $q = null)
    {
        $provider = new RedirectActiveDataProvider([
            'user' => $user ? User::findOne((int)$user) : null,
            'search' => $q,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    /**
     * @param int|null $type
     * @return string|Response
     */
    public function actionCreate($type = null)
    {
        $redirect = new Redirect();
        $redirect->type = $type;
        $request = Yii::$app->getRequest();

        if (!Yii::$app->getUser()->can('redirectCreate', ['redirect' => $redirect])) {
            throw new ForbiddenHttpException();
        }

        if ($redirect->load($request->post()) && $redirect->insert()) {
            $this->success(Yii::t('skeleton', 'The redirect rule was created.'));
            return $this->redirect(array_merge($request->get(), ['index']));
        }

        /** @noinspection MissedViewInspection */
        return $this->render('create', [
            'redirect' => $redirect,
        ]);
    }

    /**
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate(int $id)
    {
        $redirect = $this->findRedirect($id);
        $request = Yii::$app->getRequest();

        if ($redirect->load($request->post())) {
            if ($redirect->update()) {
                $this->success(Yii::t('skeleton', 'The redirect rule was updated.'));
            }

            if (!$redirect->hasErrors()) {
                return $this->redirect(array_merge($request->get(), ['update', 'id' => $redirect->id]));
            }
        }

        return $this->render('update', [
            'redirect' => $redirect,
        ]);
    }

    /**
     * @param int $id
     * @param int|null $previous
     * @return string|Response
     */
    public function actionDelete(int $id, $previous = null)
    {
        $redirect = $this->findRedirect($id);

        if ($redirect->delete()) {
            $this->success(Yii::t('skeleton', 'The redirect rule was deleted.'));
        }

        $this->error($redirect);
        return $this->redirect($previous ? ['update', 'id' => $previous] : array_merge(Yii::$app->getRequest()->get(), ['index']));
    }

    /**
     * @return Response
     */
    public function actionDeleteAll()
    {
        $request = Yii::$app->getRequest();

        if ($redirectIds = array_map('intval', $request->post('selection', []))) {
            $redirects = Redirect::findAll(['id' => $redirectIds]);
            $isDeleted = false;

            foreach ($redirects as $redirect) {
                if (Yii::$app->getUser()->can('redirectUpdate', ['redirect' => $redirect])) {
                    if ($redirect->delete()) {
                        $isDeleted = true;
                    }

                    $this->error($redirect);
                }
            }

            if ($isDeleted) {
                $this->success(Yii::t('skeleton', 'The selected redirect rules were deleted.'));
            }
        }

        return $this->redirect($request->get('redirect', array_merge($request->get(), ['index'])));
    }

    /**
     * @param int $id
     * @return Redirect
     */
    protected function findRedirect($id)
    {
        if (!$redirect = Redirect::findOne((int)$id)) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can('redirectCreate', ['redirect' => $redirect])) {
            throw new ForbiddenHttpException();
        }

        return $redirect;
    }
}