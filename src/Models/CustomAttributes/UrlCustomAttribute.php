<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use yii\base\Model;

/**
 * The rule and the field have to agree on what a URL is, or the form refuses a value the application accepts and
 * the other way round. So there is no `defaultScheme`, which `<input type="url">` makes unreachable — the browser
 * answers a scheme-less value with a bubble of its own — and `enableIDN` is on, which is why the skeleton requires
 * `ext-intl`: without it a non-ASCII *host* was refused while a non-ASCII *path* passed. A `relative()` URL is a
 * text input, since the browser refuses `/path` in a URL input; it accepts a path, a query or a fragment, never a
 * scheme-less host (`example.com`, `//example.com`).
 */
class UrlCustomAttribute extends TextCustomAttribute
{
    protected bool $relative = false;

    public function relative(bool $relative = true): static
    {
        $this->relative = $relative;
        return $this;
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        $rules = parent::getValidationRules($owner);
        $url = ['url', 'defaultScheme' => null, 'enableIDN' => true];

        if (!$this->relative) {
            return [...$rules, $url];
        }

        $isRelative = static fn (Model $model, string $attribute): bool => self::isRelative($model->$attribute);

        return [
            ...$rules,
            [...$url, 'when' => static fn (Model $model, string $attribute): bool => !$isRelative($model, $attribute)],
            ['match', 'pattern' => '/^\\S+$/u', 'when' => $isRelative],
        ];
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = InputField::make();
        return $this->configureField($this->relative ? $field : $field->type('url'), $owner);
    }

    private static function isRelative(mixed $value): bool
    {
        return is_string($value) && preg_match('#^(/(?!/)|[?\#])#', $value) === 1;
    }
}
