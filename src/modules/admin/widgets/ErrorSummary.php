<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Alert;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;

class ErrorSummary extends Widget
{
    public ?string $icon = 'exclamation-triangle';
    public array $errors = [];
    public bool $showAllErrors = true;
    protected string|false|null $title = null;

    /**
     * @var Model[]
     */
    protected array $models = [];

    public static function forModel(Model $model): static
    {
        return static::make()->models($model);
    }

    public function render(): string
    {
        if ($this->models) {
            $this->errors = $this->getModelErrors();
        }

        if (!$this->errors) {
            return '';
        }

        $content = $this->renderAlert();

        return Container::make()
            ->html($content)
            ->render();
    }

    protected function getModelErrors(): array
    {
        $errors = [];

        foreach ($this->models as $model) {
            $errors = array_unique(array_merge($errors, $model->getErrorSummary($this->showAllErrors)));
        }

        return array_values($errors);
    }

    public function models(array|Model $model): static
    {
        if ($model instanceof ActiveRecord) {
            $this->title ??= $model->getIsNewRecord()
                ? Yii::t('skeleton', 'The record could not be created:')
                : Yii::t('skeleton', 'The record could not be updated:');
        }

        $this->models = is_array($model) ? $model : [$model];
        return $this;
    }

    public function title(string|false|null $title): static
    {
        $this->title = $title;
        return $this;
    }

    protected function renderAlert(): string
    {
        return Alert::make()
            ->html($this->renderHeader())
            ->addHtml($this->renderErrors())
            ->icon($this->icon)
            ->status('danger')
            ->render();
    }

    protected function renderErrors(): string
    {
        return count($this->errors) === 1 ? Html::tag(reset($this->errors)) : Ul::tag($this->errors);
    }

    protected function renderHeader(): string
    {
        if (!$this->title) {
            return '';
        }

        return Div::make()
            ->class('alert-heading')
            ->html($this->title)
            ->render();
    }
}
