<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Closure;
use Composer\InstalledVersions;
use Hirtz\Skeleton\Caching\CacheCounter;
use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Mcp\OAuth\ClientMetadataFetcher;
use Hirtz\Skeleton\Mcp\OAuth\ResourceUri;
use Hirtz\Skeleton\Mcp\Tools\CreateRecordTool;
use Hirtz\Skeleton\Mcp\Tools\DescribeResourceTool;
use Hirtz\Skeleton\Mcp\Tools\FindRecordsTool;
use Hirtz\Skeleton\Mcp\Tools\FindTrailTool;
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
use Hirtz\Skeleton\Web\Request;
use Yii;
use yii\base\Component;

/**
 * Bundles append their resources and tools as closures, so registering one autoloads no class.
 */
class Mcp extends Component
{
    /**
     * @var list<Closure(): Resource<covariant ActiveRecord&AdminModelInterface>>
     */
    public array $resources = [];

    /**
     * @var list<Closure(self): ?ToolInterface>
     */
    public array $tools = [];

    /**
     * @var list<Closure(): Prompt>
     */
    public array $prompts = [];

    /**
     * @var list<Closure(self): ?string>
     */
    public array $instructions = [];

    public bool $enableResources = true;

    public bool $enableHttp = false;

    /**
     * @var list<string>
     */
    public array $allowedOrigins = [];

    public int $authenticationAttemptLimit = 10;

    public int $authenticationAttemptDuration = 900;

    public int $tokenLength = 48;

    /**
     * Needs {@see self::$enableHttp}.
     */
    public bool $enableOAuth = false;

    public int $accessTokenLifetime = 3600;
    public int $refreshTokenLifetime = 2592000;

    /**
     * Empty allows any client.
     *
     * @var list<string>
     */
    public array $allowedClientIds = [];

    /**
     * @var array<string, Resource<covariant ActiveRecord&AdminModelInterface>>|null
     */
    private ?array $resolvedResources = null;

    private ?string $client = null;

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
            new FindTrailTool($this),
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
     * Only the token's HMAC is stored, and a `null` lifetime never expires.
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
    public function findToken(string $token, ?string $resource = null): ?UserToken
    {
        $record = UserToken::find()
            ->whereToken($token)
            ->andWhere(['type' => [UserToken::TYPE_MCP_READ, UserToken::TYPE_MCP_WRITE]])
            ->unexpired()
            ->with(['user', 'grant'])
            ->one();

        $user = $record?->user;

        if ($user === null || ($user->isDisabled() && !$user->isOwner())) {
            return null;
        }

        if ($record->grant_id !== null) {
            $grant = $record->grant;

            if (!$this->enableOAuth || $grant === null || ($resource !== null && !ResourceUri::equals($grant->resource, $resource))) {
                return null;
            }

            $grant->touchLastUsedAt();
        } else {
            $record->touchLastUsedAt();
        }

        return $record;
    }

    /**
     * A token that exists but expired, or whose grant was revoked, is a client to send to refresh, not a guess.
     */
    public function isUnknownToken(string $token): bool
    {
        return !UserToken::find()->whereToken($token)->exists();
    }

    /**
     * Called after the lookup, since the clients of a remote connector share its few addresses and a valid token must
     * never be refused.
     */
    public function countAuthenticationAttempt(string $ip): bool
    {
        $cacheKey = [self::class, 'authentication-attempts', $ip];

        if (CacheCounter::get($cacheKey) >= $this->authenticationAttemptLimit) {
            return false;
        }

        CacheCounter::increment($cacheKey, $this->authenticationAttemptDuration);
        return true;
    }

    public function setClient(?string $client): void
    {
        $this->client = $client !== null ? mb_substr($client, 0, 100) : null;
    }

    public function getClient(): ?string
    {
        return $this->client;
    }

    public function isOAuthEnabled(): bool
    {
        return $this->enableHttp && $this->enableOAuth;
    }

    /**
     * Built from the request, since a URL manager would add a language prefix where a page is localised.
     */
    public function getResourceUrl(): string
    {
        return $this->getBaseUrl() . '/mcp';
    }

    public function getResourceMetadataUrl(): string
    {
        return $this->getBaseUrl() . '/.well-known/oauth-protected-resource/mcp';
    }

    public function getIssuer(): string
    {
        return Request::current()?->getHostInfo() ?? '';
    }

    protected function getBaseUrl(): string
    {
        $request = Request::current();
        return $request ? $request->getHostInfo() . $request->getBaseUrl() : '';
    }

    public function getClientMetadataFetcher(): ClientMetadataFetcher
    {
        return Yii::$container->get(ClientMetadataFetcher::class);
    }

    /**
     * @return list<Prompt>
     */
    public function getPrompts(): array
    {
        return array_map(fn (Closure $closure): Prompt => $closure(), $this->prompts);
    }

    public function getInstructions(bool $readOnly = false): string
    {
        $i18n = Yii::$app->getI18n();
        $languages = [];

        foreach ($i18n->getLanguages() as $language) {
            $suffix = substr($i18n->getAttributeName('x', $language), 1);
            $languages[] = $i18n->getLabel($language) . ' (' . ($suffix !== '' ? $suffix : 'no suffix') . ')';
        }

        $paragraphs = [
            'This server manages the content of the website "' . Yii::$app->name . '" as the user who connected it, '
            . 'with that user\'s permissions.',
            'Start with list_resources, then call describe_resource for a resource (and type) before writing: it '
            . 'names the attributes, the type and status ids with their names, and the tags an HTML attribute keeps. '
            . 'Find records with find_records ("filter" on columns, "query" for text) and read one with get_record.',
            'A translated attribute has one property per content language: ' . implode(', ', $languages) . '.',
            $readOnly
                ? 'This connection is read-only.'
                : 'Every write is saved at once and shown on the website when the record\'s status is enabled, so '
                . 'create records as drafts unless asked to publish. Pass the version that get_record answered '
                . 'to update_record, which then refuses to overwrite an edit made since.',
        ];

        foreach ($this->instructions as $closure) {
            $paragraphs[] = $closure($this);
        }

        return implode("\n\n", array_filter($paragraphs));
    }

    public function createServer(bool $readOnly = false): Server
    {
        return new Server(
            new ToolCollection($this->getTools(), $readOnly),
            Yii::$app->name,
            InstalledVersions::getPrettyVersion('davidhirtz/yii2-skeleton') ?? 'dev',
            $this->enableResources && $this->getResources() ? new RecordResources($this) : null,
            $this->getPrompts(),
            $this->getInstructions($readOnly),
        );
    }
}
