<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Console\Controllers;

use Hirtz\Skeleton\Console\Controllers\Traits\UserIdentityTrait;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Transports\StdioTransport;
use Hirtz\Skeleton\Models\Queries\UserTokenQuery;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;
use yii\console\Controller;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Serves the content tools to an agent over MCP and manages the tokens of the HTTP endpoint.
 */
class McpController extends Controller
{
    use UserIdentityTrait;

    /**
     * @var bool whether to offer only the tools that read
     */
    public bool $readOnly = false;

    /**
     * @var int|null the days until the token expires, never when it is not given
     */
    public ?int $expires = null;

    /**
     * @param string $actionID
     * @return list<string>
     */
    #[Override]
    public function options($actionID): array
    {
        $options = match ($actionID) {
            'serve' => ['user', 'readOnly'],
            'token' => ['user', 'readOnly', 'expires'],
            default => ['user'],
        };

        return array_values([...parent::options($actionID), ...$options]);
    }

    #[Override]
    public function beforeAction($action): bool
    {
        return parent::beforeAction($action) && $this->setUserIdentity();
    }

    /**
     * Answers MCP messages on STDIN until it closes, as the given user.
     */
    public function actionServe(): int
    {
        // A notice on STDOUT would corrupt the stream
        ini_set('display_errors', 'stderr');

        $transport = new StdioTransport(Mcp::getComponent()->createServer($this->readOnly));
        $transport->run();

        return ExitCode::OK;
    }

    /**
     * Issues a token for the HTTP endpoint, shown once.
     */
    public function actionToken(): int
    {
        $user = $this->getUser();
        $token = Mcp::getComponent()->issueToken($user, $this->readOnly, $this->expires ? $this->expires * 86400 : null);

        $this->stdout(($this->readOnly ? 'Read-only' : 'Read and write') . " token for {$user->getUsername()}:" . PHP_EOL);
        $this->stdout($token . PHP_EOL, Console::FG_GREEN);
        $this->stdout('It is shown once and cannot be retrieved later.' . PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Lists the MCP tokens of the user, never the tokens themselves.
     */
    public function actionTokens(): int
    {
        $tokens = $this->findTokens()->all();

        if (!$tokens) {
            $this->stdout('No MCP tokens.' . PHP_EOL);
        }

        foreach ($tokens as $token) {
            $this->stdout(implode("\t", [
                "#$token->id",
                $token->type,
                'created ' . $token->created_at,
                $token->expires_at ? "expires $token->expires_at" : 'never expires',
            ]) . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Revokes one MCP token of the user, or all of them.
     *
     * @param int|null $id the token, as `mcp/tokens` lists it
     */
    public function actionRevoke(?int $id = null): int
    {
        $query = $this->findTokens();

        if ($id !== null) {
            $query->andWhere(['id' => $id]);
        }

        $count = 0;

        foreach ($query->all() as $token) {
            $count += (int)$token->delete();
        }

        if ($id !== null && !$count) {
            $this->stderr("No MCP token #$id found." . PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $this->stdout("Revoked $count MCP " . ($count === 1 ? 'token' : 'tokens') . '.' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * @return UserTokenQuery<UserToken>
     */
    protected function findTokens(): UserTokenQuery
    {
        return UserToken::find()
            ->whereUser($this->getUser()->id)
            ->andWhere(['grant_id' => null, 'type' => [UserToken::TYPE_MCP_READ, UserToken::TYPE_MCP_WRITE]])
            ->orderBy(['id' => SORT_ASC]);
    }

    protected function getUser(): User
    {
        $user = WebUser::current()?->getIdentity();

        if (!$user instanceof User) {
            throw new Exception('No user found.');
        }

        return $user;
    }
}
