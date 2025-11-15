<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Alert;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Ul;
use Stringable;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;

class ErrorSummary extends Stringable
{
    protected ?string $icon = 'exclamation-triangle';
    protected array $errors = [];
    protected bool $showAllErrors = true;
    protected string|false|null $title = null;

    /**
     * @var Model[]
     */
    protected array $models = [];

    public static function forModel(Model $model): static
    {
        return (new self())->models($model);
    }

    public function icon(string|null $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function errors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
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

    public function showAllErrors(bool $showAllErrors): static
    {
        $this->showAllErrors = $showAllErrors;
        return $this;
    }

    public function title(string|false|null $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function render(): string
    {
        $this->errors ??= $this->getModelErrors();
        return $this->errors ? Container::make()->html($this->renderAlert())->render() : '';
    }

    protected function getModelErrors(): array
    {
        $errors = [];

        foreach ($this->models as $model) {
            $errors = array_unique([...$errors, ...$model->getErrorSummary($this->showAllErrors)]);
        }

        return array_values($errors);
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

    public function __toString(): string
    {
        return $this->render();
    }
}
