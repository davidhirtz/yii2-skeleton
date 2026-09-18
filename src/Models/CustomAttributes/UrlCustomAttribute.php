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
 * `ext-intl`: without it a non-ASCII *host* was refused while a non-ASCII *path* passed.
 */
class UrlCustomAttribute extends TextCustomAttribute
{
    #[Override]
    protected function getValidationRules(Model $owner): array
    {
        return [
            ...parent::getValidationRules($owner),
            ['url', 'defaultScheme' => null, 'enableIDN' => true],
        ];
    }

    #[Override]
    public function createField(Model $owner): Field
    {
        return $this->configureField(InputField::make()->type('url'), $owner);
    }
}
