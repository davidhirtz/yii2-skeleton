<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Controller;
use Hirtz\Skeleton\Web\DbSession;
use Hirtz\Skeleton\Web\LoginHandoff;
use Override;
use Yii;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @extends Controller<Module>
 */
class SsoController extends Controller
{
    #[Override]
    public function behaviors(): array
    {
        return [
            ...parent::behaviors(),
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['callback', 'logout'],
                    ],
                ],
            ],
        ];
    }

    #[Override]
    public function beforeAction($action): bool
    {
        // The callback is reached by a redirect from the auth host, which has no token of this host's.
        $this->enableCsrfValidation = false;

        if ($action->id !== 'callback' && $this->webuser->getAuthHostInfo() !== $this->request->getHostInfo()) {
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(string $return, string $state): Response
    {
        $user = $this->webuser->getIdentity() ?? throw new ForbiddenHttpException();
        return $this->redirect(LoginHandoff::create()->issue($user, $return, $state));
    }

    public function actionCallback(string $token, string $tag, string $return): Response
    {
        $this->sendNoReferrer();

        $handoff = LoginHandoff::create();
        $user = $handoff->consume($token, $tag, $return);

        $this->webuser->loginByHandoff($user, $tag);
        Application::current()->getSession()->set($handoff->tagSessionParam, $tag);

        return $this->redirect($return);
    }

    public function actionLogout(string $tag, int $expires, string $return, string $signature): Response
    {
        $handoff = LoginHandoff::create();

        if (!$handoff->validateLogout($tag, $expires, $return, $signature)) {
            throw new BadRequestHttpException(Yii::t('skeleton', 'LOGIN_HANDOFF_ERROR'));
        }

        $session = Application::current()->getSession();

        if ($session instanceof DbSession) {
            $session->destroyTaggedSessions($tag);
        }

        // Only the session that handed out the tag: another browser's login is left alone.
        if (
            !$this->webuser->getIsGuest()
            && LoginHandoff::getTag($session->getId()) === $tag
            && $this->webuser->logout()
        ) {
            $this->success(Yii::t('skeleton', 'ACCOUNT_LOGOUT_SUCCESS'));
        }

        return $this->redirect($return);
    }
}
