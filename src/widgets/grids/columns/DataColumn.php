<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids\columns;

use Closure;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use Override;
use Stringable;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\i18n\Formatter;

class DataColumn extends Column
{
    public function __construct(
        GridView $grid,
        protected Formatter $formatter,
        protected ?string $attribute = null,
        protected ?string $label = null,
        protected string|null|Closure $value = null,
        protected string $format = 'text',
        string|null|Closure $content = null,
        array|Closure $contentAttributes = [],
        ?string $header = null,
        array $headerAttributes = [],
        protected array $sortLinkAttributes = [],
        bool $visible = true,
        protected bool $enableSorting = true,
        protected bool $encodeLabel = true,
        string $emptyCell = '&nbsp;',
    ) {
        parent::__construct(
            $grid,
            $content,
            $contentAttributes,
            $header,
            $headerAttributes,
            $visible,
            $emptyCell
        );
    }

    #[Override]
    protected function renderHeaderCellContent(): string|Stringable
    {
        if ($this->header !== null || $this->label === null && $this->attribute === null) {
            return parent::renderHeaderCellContent();
        }

        $label = $this->getHeaderCellLabel();

        if ($this->encodeLabel) {
            $label = Html::encode($label);
        }

        if (
            $this->attribute !== null
            && $this->enableSorting
            && ($sort = $this->grid->dataProvider->getSort()) !== false && $sort->hasAttribute($this->attribute)
        ) {
            return $sort->link($this->attribute, [
                ...$this->sortLinkAttributes,
                'label' => $label,
            ]);
        }

        return $label;
    }

    protected function getHeaderCellLabel(): ?string
    {
        return $this->attribute
            ? $this->grid->getModel()?->getAttributeLabel($this->attribute) ?? Inflector::camel2words($this->attribute)
            : null;
    }

    protected function getDataCellValue(Model $model, string|int $key, int $index): mixed
    {
        if ($this->value instanceof Closure) {
            return call_user_func($this->value, $model, $key, $index, $this);
        }

        $key = $this->value ?? $this->attribute ?? null;
        return $key ? ArrayHelper::getValue($model, $this->value ?? $this->attribute) : null;
    }

    protected function renderDataCellContent(Model $model, string|int $key, int $index): string|Stringable
    {
        return $this->content === null
            ? $this->formatter->format($this->getDataCellValue($model, $key, $index), $this->format)
            : parent::renderDataCellContent($model, $key, $index);
    }
}
