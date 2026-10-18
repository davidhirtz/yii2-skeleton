<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Controllers;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\OAuth\AuthorizationCode;
use Hirtz\Skeleton\Mcp\OAuth\AuthorizationRequest;
use Hirtz\Skeleton\Mcp\OAuth\OAuthException;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Modules\Admin\Controllers\Traits\UserTrait;
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
 * @extends Controller<Module>
 */
class GrantController extends Controller
{
    use UserTrait;

    final public const string TOKEN_FLASH = 'mcpToken';

    /**
     * @var list<int> the days a token created in the admin may last, besides never expiring
     */
    public array $tokenLifetimes = [30, 90, 365];

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
                        'actions' => ['authorize', 'create-token', 'index', 'revoke', 'revoke-token'],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view'],
                        'roles' => [User::AUTH_USER],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create-token' => ['post'],
                    'revoke' => ['post'],
                    'revoke-token' => ['post'],
                ],
            ],
        ];
    }

    #[Override]
    public function beforeAction($action): bool
    {
        $mcp = Mcp::getComponent();

        if (!$mcp->enableHttp || ($action->id === 'authorize' && !$mcp->isOAuthEnabled())) {
            throw new NotFoundHttpException();
        }

        return parent::beforeAction($action);
    }

    /**
     * A POST approves or denies the request its query string carries. The answer leaves the site, so the form is
     * not boosted.
     */
    public function actionAuthorize(): Response|string
    {
        $mcp = Mcp::getComponent();
        $issuer = $mcp->getIssuer();
        $resource = $mcp->getResourceUrl();

        // Shown rather than sent to the client: a redirect nobody clicked would make the admin an open redirector
        // for any host that publishes a metadata document (RFC 9700 §4.11.2)
        try {
            $request = AuthorizationRequest::fromParams($this->request->get(), $mcp->getClientMetadataFetcher(), $mcp->allowedClientIds);
            $request->validate($resource);
        } catch (OAuthException $exception) {
            $this->response->setStatusCode(400);

            return $this->render('error', [
                'message' => $exception->getMessage(),
            ]);
        }

        if ($this->request->getIsPost()) {
            if (!$this->request->post('approve')) {
                return $this->redirect($request->getRedirectUrl(['error' => OAuthException::ACCESS_DENIED], $issuer));
            }

            $readOnly = $request->getScope() === OAuthGrant::SCOPE_READ || $this->request->post('readOnly');
            $grant = $this->saveGrant($request, $resource, $readOnly ? OAuthGrant::SCOPE_READ : OAuthGrant::SCOPE_WRITE);

            $code = new AuthorizationCode(
                $grant->id,
                $request->client->clientId,
                $request->redirectUri,
                $request->getCodeChallenge(),
                $resource,
            );

            return $this->redirect($request->getRedirectUrl(['code' => $code->issue()], $issuer));
        }

        return $this->render('authorize', [
            'request' => $request,
            'user' => $this->webuser->getIdentity(),
        ]);
    }

    public function actionIndex(): Response|string
    {
        $user = $this->webuser->getIdentity();

        $token = Application::current()->getSession()->getFlash(self::TOKEN_FLASH, delete: true);

        return $this->render('index', [
            'grants' => $this->getGrantProvider($user),
            'tokens' => $this->getTokenProvider($user),
            'token' => is_string($token) ? $token : null,
            'tokenLifetimes' => $this->tokenLifetimes,
            'user' => $user,
        ]);
    }

    public function actionView(int $user): Response|string
    {
        $user = $this->findUser($user, User::AUTH_USER);

        return $this->render('view', [
            'grants' => $this->getGrantProvider($user),
            'tokens' => $this->getTokenProvider($user),
            'user' => $user,
        ]);
    }

    public function actionRevoke(int $id): Response|string
    {
        $grant = OAuthGrant::find()
            ->where(['id' => $id])
            ->with('user')
            ->one();

        if ($grant === null) {
            throw new NotFoundHttpException();
        }

        $isOwn = $grant->user_id === $this->webuser->getId();

        if (!$isOwn && !$this->webuser->can(User::AUTH_USER, ['user' => $grant->user])) {
            throw new ForbiddenHttpException();
        }

        if ($grant->delete()) {
            $this->success(Yii::t('skeleton', 'GRANT_SUCCESS_REVOKED'));
        }

        return $this->redirect($isOwn ? ['index'] : ['view', 'user' => $grant->user_id]);
    }

    /**
     * The token is shown once, on the page the redirect leads to.
     */
    public function actionCreateToken(): Response
    {
        $user = $this->webuser->getIdentity();
        $days = (int)$this->request->post('expires');
        $lifetime = in_array($days, $this->tokenLifetimes, true) ? $days * 86400 : null;

        $token = Mcp::getComponent()->issueToken($user, (bool)$this->request->post('readOnly'), $lifetime);
        Application::current()->getSession()->setFlash(self::TOKEN_FLASH, $token);

        return $this->redirect(['index']);
    }

    public function actionRevokeToken(int $id): Response
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

        return $this->redirect($isOwn ? ['index'] : ['view', 'user' => $token->user_id]);
    }

    /**
     * Approving the same client again replaces the scope it was granted and keeps its tokens, but for the write
     * tokens of a grant narrowed to reading.
     */
    protected function saveGrant(AuthorizationRequest $request, string $resource, string $scope): OAuthGrant
    {
        $userId = (int)$this->webuser->getId();

        $grant = OAuthGrant::find()
            ->where([
                'user_id' => $userId,
                'client_id' => $request->client->clientId,
                'resource' => $resource,
            ])
            ->one() ?? OAuthGrant::create();

        $grant->user_id = $userId;
        $grant->client_id = $request->client->clientId;
        $grant->client_name = $request->client->clientName;
        $grant->resource = $resource;
        $grant->scope = $scope;

        $grant->save();

        if ($grant->isReadOnly()) {
            UserToken::deleteAll(['grant_id' => $grant->id, 'type' => UserToken::TYPE_MCP_WRITE]);
        }

        return $grant;
    }

    protected function getGrantProvider(User $user): ?ActiveDataProvider
    {
        if (!Mcp::getComponent()->isOAuthEnabled()) {
            return null;
        }

        return new ActiveDataProvider([
            'query' => OAuthGrant::find()
                ->where(['user_id' => $user->id])
                ->orderBy(['created_at' => SORT_DESC]),
            'sort' => false,
            'pagination' => false,
        ]);
    }

    protected function getTokenProvider(User $user): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => UserToken::find()
                ->whereUser($user->id)
                ->andWhere(['grant_id' => null, 'type' => [UserToken::TYPE_MCP_READ, UserToken::TYPE_MCP_WRITE]])
                ->orderBy(['created_at' => SORT_DESC]),
            'sort' => false,
            'pagination' => false,
        ]);
    }
}
