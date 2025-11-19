<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\traits\TagTitleTrait;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\widgets\Alert;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;

class ErrorSummary extends Widget
{
    use TagTitleTrait;

    public ?string $icon = 'exclamation-triangle';
    public bool $showAllErrors = true;

    protected ?array $errors = null;

    /**
     * @var Model[]
     */
    protected array $models = [];

    public function icon(string|null $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function errors(?array $errors): static
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

    protected function renderContent(): string|Stringable
    {
        $this->errors ??= $this->getModelErrors();
        return $this->errors ? $this->getAlert() : '';
    }

    protected function getModelErrors(): array
    {
        $errors = [];

        foreach ($this->models as $model) {
            $errors = array_unique([...$errors, ...$model->getErrorSummary($this->showAllErrors)]);
        }

        return array_values($errors);
    }

    protected function getAlert(): Stringable
    {
        return Alert::make()
            ->content($this->getHeader())
            ->addContent($this->getErrors())
            ->icon($this->icon)
            ->danger();
    }

    protected function getErrors(): Stringable
    {
        return count($this->errors) === 1
            ? Div::make()
                ->content(reset($this->errors))
            : Ul::make()
                ->items(...$this->errors);
    }

    protected function getHeader(): ?Stringable
    {
        return $this->title
            ? Div::make()
                ->class('alert-heading')
                ->content($this->title)
            : null;
    }
}
