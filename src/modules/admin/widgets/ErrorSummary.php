<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets;

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
    public string|false|null $title = null;

    /**
     * @var Model[]
     */
    private array $models = [];

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

    public function setModels(array|Model $model): void
    {
        if ($model instanceof ActiveRecord) {
            $this->title ??= $model->getIsNewRecord()
                ? Yii::t('skeleton', 'The record could not be created:')
                : Yii::t('skeleton', 'The record could not be updated:');
        }

        $this->models = is_array($model) ? $model : [$model];
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
        return Ul::make()
            ->items($this->errors)
            ->render();
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
