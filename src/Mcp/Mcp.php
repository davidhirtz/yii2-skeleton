<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Closure;
use Composer\InstalledVersions;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Mcp\Tools\CreateRecordTool;
use Hirtz\Skeleton\Mcp\Tools\DescribeResourceTool;
use Hirtz\Skeleton\Mcp\Tools\FindRecordsTool;
use Hirtz\Skeleton\Mcp\Tools\GetRecordTool;
use Hirtz\Skeleton\Mcp\Tools\ListResourcesTool;
use Hirtz\Skeleton\Mcp\Tools\ReorderTool;
use Hirtz\Skeleton\Mcp\Tools\ResourceTool;
use Hirtz\Skeleton\Mcp\Tools\SetStatusTool;
use Hirtz\Skeleton\Mcp\Tools\ToolCollection;
use Hirtz\Skeleton\Mcp\Tools\ToolInterface;
use Hirtz\Skeleton\Mcp\Tools\UpdateRecordTool;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Yii;
use yii\base\Component;

/**
 * The `mcp` application component. Each bundle appends its resources and tools through its default config, as
 * closures, so registering one autoloads no class.
 */
class Mcp extends Component
{
    /**
     * @var list<Closure(): Resource<covariant ActiveRecord&AdminModelInterface>>
     */
    public array $resources = [];

    /**
     * @var list<Closure(self): ?ToolInterface> a closure answering `null` offers no tool
     */
    public array $tools = [];

    /**
     * @var bool whether `POST /mcp` answers, authenticated by the tokens `mcp/token` issues
     */
    public bool $enableHttp = false;

    /**
     * @var list<string> origins besides the request's own a browser may send, e.g. `https://claude.ai`
     */
    public array $allowedOrigins = [];

    /**
     * @var int failed authentications per IP address before the endpoint answers `429`
     */
    public int $authenticationAttemptLimit = 10;

    public int $authenticationAttemptDuration = 900;

    public int $tokenLength = 48;

    /**
     * @var array<string, Resource<covariant ActiveRecord&AdminModelInterface>>|null
     */
    private ?array $resolvedResources = null;

    public static function getComponent(): self
    {
        /** @var self $mcp */
        $mcp = Yii::$app->get('mcp');
        return $mcp;
    }

    /**
     * @return array<string, Resource<covariant ActiveRecord&AdminModelInterface>>
     */
    public function getResources(): array
    {
        if ($this->resolvedResources === null) {
            $this->resolvedResources = [];

            // A later registration replaces an earlier one of the same name, so an application's wins over a bundle's
            foreach ($this->resources as $closure) {
                $resource = $closure();
                $this->resolvedResources[$resource->getName()] = $resource;
            }
        }

        return $this->resolvedResources;
    }

    /**
     * @return Resource<covariant ActiveRecord&AdminModelInterface>|null
     */
    public function getResource(string $name): ?Resource
    {
        return $this->getResources()[$name] ?? null;
    }

    /**
     * @return list<ToolInterface>
     */
    public function getTools(): array
    {
        $tools = [
            new ListResourcesTool($this),
            new DescribeResourceTool($this),
            new FindRecordsTool($this),
            new GetRecordTool($this),
            new CreateRecordTool($this),
            new UpdateRecordTool($this),
            new SetStatusTool($this),
            new ReorderTool($this),
        ];

        foreach ($this->tools as $closure) {
            if ($tool = $closure($this)) {
                $tools[] = $tool;
            }
        }

        return array_values(array_filter(
            $tools,
            fn (ToolInterface $tool): bool => !$tool instanceof ResourceTool || $tool->isAvailable(),
        ));
    }

    /**
     * @param int|null $lifetime seconds until the token expires, `null` for one that never does
     * @return string the token, which only its HMAC is stored for
     */
    public function issueToken(User $user, bool $readOnly, ?int $lifetime = null): string
    {
        $token = Yii::$app->getSecurity()->generateRandomString($this->tokenLength);
        UserToken::issue($user->id, $readOnly ? UserToken::TYPE_MCP_READ : UserToken::TYPE_MCP_WRITE, $token, $lifetime);

        return $token;
    }

    /**
     * A user who could not log in is not authenticated by a token either.
     */
    public function findToken(string $token): ?UserToken
    {
        $record = UserToken::find()
            ->whereToken($token)
            ->andWhere(['type' => [UserToken::TYPE_MCP_READ, UserToken::TYPE_MCP_WRITE]])
            ->unexpired()
            ->with('user')
            ->one();

        $user = $record?->user;

        if ($user === null || ($user->isDisabled() && !$user->isOwner())) {
            return null;
        }

        return $record;
    }

    public function createServer(bool $readOnly = false): Server
    {
        return new Server(
            new ToolCollection($this->getTools(), $readOnly),
            Yii::$app->name,
            InstalledVersions::getPrettyVersion('davidhirtz/yii2-skeleton') ?? 'dev',
        );
    }
}
