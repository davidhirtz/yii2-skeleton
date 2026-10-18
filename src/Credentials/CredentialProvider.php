<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Credentials;

use Closure;
use Hirtz\Skeleton\Models\CustomAttributes\CustomAttribute;
use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Yii;

/**
 * A third-party service a user may store a key of their own for, declared by the bundle that calls it.
 */
class CredentialProvider
{
    protected ?string $label = null;
    protected ?string $url = null;
    protected ?Closure $verify = null;

    /**
     * @var list<CustomAttribute>
     */
    protected array $fields = [];

    final public function __construct(public readonly string $name)
    {
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Where a user gets a key, shown beside the form.
     */
    public function url(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * What a user enters for the service, a single `key` unless the service names more, such as an ID and a secret.
     */
    public function fields(CustomAttribute ...$fields): static
    {
        $this->fields = array_values($fields);
        return $this;
    }

    /**
     * @param Closure(array<string, string>): bool $verify a cheap call to the service, so a mistyped key is refused
     * when it is saved
     */
    public function verify(Closure $verify): static
    {
        $this->verify = $verify;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? $this->name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @return list<CustomAttribute>
     */
    public function getFields(): array
    {
        return $this->fields ?: [self::createSecretField('key', Yii::t('skeleton', 'CREDENTIAL_SECRET_LABEL'))];
    }

    /**
     * @param array<string, string> $values
     */
    public function isValid(array $values): bool
    {
        return $this->verify === null || ($this->verify)($values);
    }

    /**
     * A required field typed as a password, so a key shows on no screen while it is entered.
     */
    public static function createSecretField(string $name, string $label): TextCustomAttribute
    {
        return TextCustomAttribute::make($name)
            ->label($label)
            ->required()
            ->field(static fn (Field $field) => $field instanceof InputField
                ? $field->type('password')->attribute('autocomplete', 'off')
                : $field);
    }
}
