<?php

declare(strict_types=1);

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

class RedirectController extends Controller
{
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['create', 'delete', 'delete-all', 'index', 'update'],
                        'roles' => [Redirect::AUTH_REDIRECT_CREATE],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $user = null, ?string $q = null): Response|string
    {
        $provider = Yii::$container->get(RedirectActiveDataProvider::class, [], [
            'user' => User::findOne($user),
            'search' => $q,
        ]);

        return $this->render('index', [
            'provider' => $provider,
        ]);
    }

    public function actionCreate(?int $type = null): Response|string
    {
        $redirect = Redirect::create();
        $redirect->type = $type;
        $request = Yii::$app->getRequest();

        if (!Yii::$app->getUser()->can('redirectCreate', ['redirect' => $redirect])) {
            throw new ForbiddenHttpException();
        }

        if ($redirect->load($request->post()) && $redirect->insert()) {
            $this->success(Yii::t('skeleton', 'The redirect rule was created.'));
            return $this->redirect(array_merge($request->get(), ['index']));
        }

        return $this->render('create', [
            'redirect' => $redirect,
        ]);
    }

    public function actionUpdate(int $id): Response|string
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

    public function actionDelete(int $id, ?int $previous = null): Response|string
    {
        $redirect = $this->findRedirect($id);

        if ($redirect->delete()) {
            $this->success(Yii::t('skeleton', 'The redirect rule was deleted.'));
        }

        $this->error($redirect);
        return $this->redirect($previous ? ['update', 'id' => $previous] : array_merge(Yii::$app->getRequest()->get(), ['index']));
    }

    public function actionDeleteAll(): Response|string
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

    protected function findRedirect(int $id): Redirect
    {
        if (!$redirect = Redirect::findOne($id)) {
            throw new NotFoundHttpException();
        }

        if (!Yii::$app->getUser()->can('redirectCreate', ['redirect' => $redirect])) {
            throw new ForbiddenHttpException();
        }

        return $redirect;
    }
}
