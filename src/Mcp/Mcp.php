<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Closure;
use Composer\InstalledVersions;
use Hirtz\Skeleton\Mcp\Tools\ListResourcesTool;
use Hirtz\Skeleton\Mcp\Tools\ToolCollection;
use Hirtz\Skeleton\Mcp\Tools\ToolInterface;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * The `mcp` application component. Each bundle appends its resources and tools through its default config, as
 * closures, so registering one autoloads no class.
 */
class Mcp extends Component
{
    /**
     * @var list<Closure(): Resource>
     */
    public array $resources = [];

    /**
     * @var list<Closure(self): ?ToolInterface> a closure answering `null` offers no tool
     */
    public array $tools = [];

    /**
     * @var array<string, Resource>|null
     */
    private ?array $resolvedResources = null;

    public static function getComponent(): self
    {
        /** @var self $mcp */
        $mcp = Yii::$app->get('mcp');
        return $mcp;
    }

    /**
     * @return array<string, Resource>
     */
    public function getResources(): array
    {
        if ($this->resolvedResources === null) {
            $this->resolvedResources = [];

            foreach ($this->resources as $closure) {
                $resource = $closure();

                if (isset($this->resolvedResources[$resource->getName()])) {
                    throw new InvalidConfigException("MCP resource \"{$resource->getName()}\" is registered twice.");
                }

                $this->resolvedResources[$resource->getName()] = $resource;
            }
        }

        return $this->resolvedResources;
    }

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
        ];

        foreach ($this->tools as $closure) {
            if ($tool = $closure($this)) {
                $tools[] = $tool;
            }
        }

        return $tools;
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
