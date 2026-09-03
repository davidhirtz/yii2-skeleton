<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Routing;

use InvalidArgumentException;

/**
 * A router-agnostic route. Patterns use `{placeholder}` braces, the syntax shared by Symfony and
 * Laravel, so only the {@see RouteCompilerInterface} knows about the router in use.
 */
final class Route
{
    public const int POSITION_FIRST = 0;
    public const int POSITION_DEFAULT = 500;
    public const int POSITION_FALLBACK = 1000;

    public const string PATTERN_PATH = '.+';
    public const string PATTERN_OPTIONAL_PATH = '.*';
    public const string PATTERN_SEGMENT = '[^\/]+';
    public const string PATTERN_ID = '\d+';

    public ?string $name = null;

    /**
     * @var array<string, string>
     */
    public array $constraints = [];

    /**
     * @var array<string, scalar|null>
     */
    public array $defaults = [];

    /**
     * @var list<string>
     */
    public array $methods = [];

    public int $position = self::POSITION_DEFAULT;
    public bool $encodeParams = true;
    public ?string $host = null;
    public ?string $suffix = null;
    public RouteMode $mode = RouteMode::Both;
    public ?string $ruleClass = null;

    /**
     * @var array<string, mixed>
     */
    public array $ruleOptions = [];

    private function __construct(
        public string $pattern,
        public string $action,
        public bool $isRaw = false,
    ) {
    }

    public static function to(string $pattern, string $action): self
    {
        return new self($pattern, $action);
    }

    /**
     * Passes the pattern to the compiler untouched, for what the placeholder syntax cannot express —
     * Yii's optional trailing `?`, for instance. Raw routes are not portable.
     */
    public static function raw(string $pattern, string $action): self
    {
        return new self($pattern, $action, true);
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function where(string $placeholder, string $pattern): self
    {
        $this->constraints[$placeholder] = $pattern;
        return $this;
    }

    /**
     * @param array<string, scalar|null> $defaults
     */
    public function defaults(array $defaults): self
    {
        $this->defaults = [...$this->defaults, ...$defaults];
        return $this;
    }

    public function methods(string ...$methods): self
    {
        $this->methods = array_values(array_map(strtoupper(...), $methods));
        return $this;
    }

    public function position(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function first(): self
    {
        return $this->position(self::POSITION_FIRST);
    }

    public function last(): self
    {
        return $this->position(self::POSITION_FALLBACK);
    }

    public function withoutParamEncoding(): self
    {
        $this->encodeParams = false;
        return $this;
    }

    public function host(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    public function suffix(string $suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    public function parseOnly(): self
    {
        $this->mode = RouteMode::ParseOnly;
        return $this;
    }

    public function createOnly(): self
    {
        $this->mode = RouteMode::CreateOnly;
        return $this;
    }

    /**
     * Escape hatch for routes needing custom parsing. A route using this cannot be compiled for a
     * different router, which is the signal you want when the time comes.
     *
     * @param array<string, mixed> $options
     */
    public function useRuleClass(string $ruleClass, array $options = []): self
    {
        $this->ruleClass = $ruleClass;
        $this->ruleOptions = [...$this->ruleOptions, ...$options];

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getPlaceholders(): array
    {
        if ($this->isRaw) {
            return [];
        }

        preg_match_all('/\{(\w+)}/', $this->pattern, $matches);

        return array_values(array_unique($matches[1]));
    }

    public function equals(self $route): bool
    {
        /** @noinspection PhpNonStrictObjectEqualityInspection */
        return $this == $route;
    }

    public function validate(): void
    {
        $unknown = array_diff(array_keys($this->constraints), $this->getPlaceholders());

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                sprintf('Route "%s" constrains undefined placeholder(s): %s.', $this->pattern, implode(', ', $unknown))
            );
        }
    }
}
