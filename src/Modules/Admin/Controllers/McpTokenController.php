<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\Forms\McpTokenForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Controller;
use Override;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The access tokens of the HTTP endpoint, for a client that does not speak OAuth.
 *
 * @extends Controller<Module>
 */
class McpTokenController extends Controller
{
    final public const string TOKEN_FLASH = 'mcpToken';

    /**
     * @var list<int> the days a token may last, besides never expiring
     */
    public array $tokenLifetimes = [30, 90, 365];

    /**
     * @var int|null the lifetime preselected, `null` for a token that never expires
     */
    public ?int $defaultTokenLifetime = 90;

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
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'revoke' => ['post'],
                ],
            ],
        ];
    }

    #[Override]
    public function beforeAction($action): bool
    {
        if (!Mcp::getComponent()->enableHttp) {
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
    }

    /**
     * A new token is shown once, from the flash the create action left.
     */
    public function actionIndex(): string
    {
        $user = $this->webuser->getIdentity();
        $token = Application::current()->getSession()->getFlash(self::TOKEN_FLASH, delete: true);

        return $this->render('index', [
            'tokens' => new ActiveDataProvider([
                'query' => UserToken::find()
                    ->whereUser($user->id)
                    ->andWhere(['grant_id' => null, 'type' => [UserToken::TYPE_MCP_READ, UserToken::TYPE_MCP_WRITE]])
                    ->orderBy(['created_at' => SORT_DESC]),
                'sort' => false,
                'pagination' => false,
            ]),
            'token' => is_string($token) ? $token : null,
            'user' => $user,
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new McpTokenForm($this->webuser->getIdentity(), $this->tokenLifetimes, $this->defaultTokenLifetime);

        if ($form->load($this->request->post())) {
            if ($token = $form->create()) {
                Application::current()->getSession()->setFlash(self::TOKEN_FLASH, $token);
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'form' => $form,
        ]);
    }

    public function actionRevoke(int $id): Response
    {
        $token = UserToken::find()
            ->where(['id' => $id, 'grant_id' => null, 'type' => [UserToken::TYPE_MCP_READ, UserToken::TYPE_MCP_WRITE]])
            ->with('user')
            ->one();

        if ($token === null) {
            throw new NotFoundHttpException();
        }

        $isOwn = $token->user_id === $this->webuser->getId();

        if (!$isOwn && !$this->webuser->can(User::AUTH_USER, ['user' => $token->user])) {
            throw new ForbiddenHttpException();
        }

        if ($token->delete()) {
            $this->success(Yii::t('skeleton', 'MCP_TOKEN_SUCCESS_REVOKED'));
        }

        return $this->redirect($isOwn ? ['index'] : ['/admin/grant/view', 'user' => $token->user_id]);
    }
}
