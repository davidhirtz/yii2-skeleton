<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\CustomAttributes;

use Hirtz\Skeleton\Widgets\Forms\Fields\Field;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Override;
use yii\base\Model;

/**
 * Rule and field must agree on what a URL is: no `defaultScheme`, which `<input type="url">` makes unreachable,
 * `enableIDN` on (hence the required `ext-intl`), and `relative()` a text input, since the browser refuses `/path`.
 */
class UrlCustomAttribute extends TextCustomAttribute
{
    /**
     * Schemes without a host, which Yii's `url` validator cannot check.
     */
    private const array OPAQUE_SCHEME_PATTERNS = [
        'mailto' => '#^mailto:[^\s@?/]+@[^\s@?/]+\.[^\s@?/]+(\?\S*)?$#iu',
        'sms' => '#^sms:\+?\d[\d ()./-]*(\?\S*)?$#i',
        'tel' => '#^tel:\+?\d[\d ()./-]*$#i',
    ];

    /**
     * @var list<string>
     */
    protected array $schemes = ['http', 'https', 'mailto', 'tel'];

    protected bool $relative = false;

    /**
     * @param list<string> $schemes
     */
    public function schemes(array $schemes): static
    {
        $this->schemes = array_map(strtolower(...), $schemes);
        return $this;
    }

    public function relative(bool $relative = true): static
    {
        $this->relative = $relative;
        return $this;
    }

    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        $opaque = array_intersect_key(self::OPAQUE_SCHEME_PATTERNS, array_flip($this->schemes));
        $isOpaque = static fn (Model $model, string $attribute): bool
            => isset($opaque[self::getScheme($model->$attribute)]);
        $isRelative = fn (Model $model, string $attribute): bool => $this->relative
            && self::isRelative($model->$attribute);

        $rules = [
            ...parent::getValidationRules($owner),
            [
                'url',
                'defaultScheme' => null,
                'enableIDN' => true,
                'validSchemes' => array_values(array_diff($this->schemes, array_keys(self::OPAQUE_SCHEME_PATTERNS))),
                'when' => static fn (Model $model, string $attribute): bool => !$isOpaque($model, $attribute)
                    && !$isRelative($model, $attribute),
            ],
            ['match', 'pattern' => '/^\S+$/u', 'when' => $isRelative],
        ];

        foreach ($opaque as $scheme => $pattern) {
            $rules[] = [
                'match',
                'pattern' => $pattern,
                'when' => static fn (Model $model, string $attribute): bool
                    => self::getScheme($model->$attribute) === $scheme,
            ];
        }

        return $rules;
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        $field = InputField::make();
        return $this->configureField($this->relative ? $field : $field->type('url'), $owner);
    }

    private static function getScheme(mixed $value): string
    {
        return is_string($value) && preg_match('/^([a-z][a-z\d+.-]*):/i', $value, $matches)
            ? strtolower($matches[1])
            : '';
    }

    private static function isRelative(mixed $value): bool
    {
        return is_string($value) && preg_match('#^(/(?!/)|[?\#])#', $value) === 1;
    }
}
