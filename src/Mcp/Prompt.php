<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp;

use Closure;

class Prompt
{
    protected ?string $title = null;
    protected ?string $description = null;

    /**
     * @var array<string, array{description: string|null, required: bool}>
     */
    protected array $arguments = [];

    /**
     * @var Closure(array<string, string>): string|null
     */
    protected ?Closure $text = null;

    final public function __construct(public readonly string $name)
    {
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function title(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function argument(string $name, ?string $description = null, bool $required = false): static
    {
        $this->arguments[$name] = ['description' => $description, 'required' => $required];
        return $this;
    }

    /**
     * @param Closure(array<string, string>): string $text
     */
    public function text(Closure $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $arguments = [];

        foreach ($this->arguments as $name => $argument) {
            $arguments[] = array_filter([
                'name' => $name,
                'description' => $argument['description'],
                'required' => $argument['required'] ?: null,
            ], fn (mixed $value): bool => $value !== null);
        }

        return array_filter([
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'arguments' => $arguments ?: null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param array<array-key, mixed> $arguments
     * @return array<string, mixed>
     * @throws JsonRpcException
     */
    public function get(array $arguments): array
    {
        $values = [];

        foreach ($this->arguments as $name => $argument) {
            $value = $arguments[$name] ?? null;

            if (is_string($value) && $value !== '') {
                $values[$name] = $value;
            } elseif ($argument['required']) {
                throw new JsonRpcException(JsonRpcException::INVALID_PARAMS, "Missing required argument: $name");
            }
        }

        return array_filter([
            'description' => $this->description,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        'type' => 'text',
                        'text' => $this->text ? ($this->text)($values) : '',
                    ],
                ],
            ],
        ], fn (mixed $value): bool => $value !== null);
    }
}
