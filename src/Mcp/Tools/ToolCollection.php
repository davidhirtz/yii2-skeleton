<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\Tools;

class ToolCollection
{
    /**
     * @var array<string, ToolInterface>
     */
    private array $tools = [];

    /**
     * @param iterable<ToolInterface> $tools
     */
    public function __construct(iterable $tools, public readonly bool $readOnly = false)
    {
        foreach ($tools as $tool) {
            if (!$readOnly || $tool->isReadOnly()) {
                $this->tools[$tool->getName()] = $tool;
            }
        }
    }

    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * @return list<ToolInterface>
     */
    public function all(): array
    {
        return array_values($this->tools);
    }
}
