<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns;

use yii\grid\DataColumn;
use yii\helpers\Html;

class LinkDataColumn extends DataColumn
{
    /**
     * @var callable|null a callback function that returns the route for the count link
     */
    public mixed $route = null;

    /**
     * @var array contains the HTML attributes for the link or wrapper
     */
    public array $wrapperOptions = [];

    #[\Override]
    protected function renderDataCellContent($model, $key, $index): string
    {
        $route = is_callable($this->route) ? call_user_func($this->route, $model) : $this->route;
        $content = parent::renderDataCellContent($model, $key, $index);

        if (!$content || $content == $this->grid->emptyCell) {
            return $content;
        }

        if ($route) {
            return Html::a($content, $route, $this->wrapperOptions);
        }

        return $this->wrapperOptions ? Html::tag('div', $content, $this->wrapperOptions) : $content;
    }
}
