<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Models\Interfaces\AdminModelInterface;

class Resource
{
    /**
     * @param class-string<ActiveRecord&AdminModelInterface> $modelClass
     */
    final public function __construct(public readonly string $name, public readonly string $modelClass)
    {
    }

    /**
     * @param class-string<ActiveRecord&AdminModelInterface> $modelClass
     */
    public static function make(string $name, string $modelClass): static
    {
        return new static($name, $modelClass);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return class-string<ActiveRecord&AdminModelInterface>
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function instantiate(): ActiveRecord&AdminModelInterface
    {
        return $this->modelClass::create();
    }
}
