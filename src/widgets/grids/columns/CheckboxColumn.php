<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use davidhirtz\yii2\skeleton\assets\SelectableAssetBundle;
use davidhirtz\yii2\skeleton\html\Checkbox;
use Override;
use Stringable;
use Yii;
use yii\base\Model;

class CheckboxColumn extends Column
{
    public function __construct(
        protected array $checkboxAttributes = [],
        protected bool $multiple = true,
        protected string $name = 'selection[]',
        ...$args,
    ) {
        parent::__construct(...$args);
    }

    protected function init(): void
    {
        if ($this->multiple && substr_compare($this->name, '[]', -2, 2)) {
            $this->name .= '[]';
        }

        $this->registerClientScript();
    }

    #[Override]
    protected function getHeaderContent(): string|Stringable
    {
        if ($this->header !== null || !$this->multiple) {
            return parent::getHeaderContent();
        }

        return Checkbox::make()->attribute('data-check-all', "#{$this->grid->getId()}");
    }

    #[Override]
    protected function getBodyContent(array|Model $model, string|int $key, int $index): string|Stringable
    {
        if ($this->content !== null) {
            return parent::getBodyContent($model, $key, $index);
        }

        return Checkbox::make()
            ->attribute('data-check', $this->multiple ? 'multiple' : 'single')
            ->addAttributes($this->checkboxAttributes)
            ->name($this->name);
    }

    protected function registerClientScript(): void
    {
        Yii::$app->getView()->registerAssetBundle(SelectableAssetBundle::class);
    }
}
