<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Traits\TagTitleTrait;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Widget;
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
    protected array $excluded = [];

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

    public function excluding(array $excluded): static
    {
        $this->excluded = $excluded;
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
        $lines = [];

        foreach ($this->models as $model) {
            $errors = $this->showAllErrors ? $model->getErrors() : $model->getFirstErrors();

            foreach ($errors as $attribute => $error) {
                if (!in_array($attribute, $this->excluded, true)) {
                    $lines = [...$lines, ...(array)$error];
                }
            }
        }

        return array_values(array_unique($lines));
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
