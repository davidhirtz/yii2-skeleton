<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\I18n;

use JsonSerializable;
use Stringable;
use Yii;

/**
 * A pointer to a translatable message, stored as JSON in place of rendered text so a row written by one user
 * renders in the language of whoever reads it.
 */
class Message implements JsonSerializable, Stringable
{
    /**
     * @param array<string, mixed> $params
     */
    final public function __construct(
        public readonly string $category,
        public readonly string $key,
        public readonly array $params = [],
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function make(string $category, string $key, array $params = []): static
    {
        return new static($category, $key, $params);
    }

    /**
     * A value that is not a message pointer is literal text a previous version wrote, and is returned as such.
     */
    public static function fromJson(?string $json): ?static
    {
        if ($json === null || $json === '') {
            return null;
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !is_string($data['category'] ?? null) || !is_string($data['key'] ?? null)) {
            return static::literal($json);
        }

        $params = $data['params'] ?? [];

        return new static($data['category'], $data['key'], is_array($params) ? $params : []);
    }

    /**
     * An empty category means the key is the text itself, so `Yii::t()` is never asked for it.
     */
    public static function literal(string $text): static
    {
        return new static('', $text);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function withParams(array $params): static
    {
        return new static($this->category, $this->key, [...$this->params, ...$params]);
    }

    public function isLiteral(): bool
    {
        return $this->category === '';
    }

    public function toJson(): string
    {
        return json_encode($this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'category' => $this->category,
            'key' => $this->key,
        ];

        if ($this->params) {
            $data['params'] = $this->params;
        }

        return $data;
    }

    public function __toString(): string
    {
        return $this->isLiteral()
            ? $this->key
            : Yii::t($this->category, $this->key, $this->params);
    }
}
