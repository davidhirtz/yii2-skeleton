<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Form;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\widgets\forms\fields\Field;
use davidhirtz\yii2\skeleton\widgets\forms\footers\FormFooter;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\helpers\Inflector;

class ActiveForm extends Widget
{
    use TagAttributesTrait;
    use TagIdTrait;
    use ModelWidgetTrait;

    public array|string|false|null $action = null;

    public bool $hasStickyButtons = true;
    public string $layout = "{errors}{rows}{buttons}{footer}";

    protected array|false|null $buttons = null;
    protected array|false|null $footer = null;

    /**
     * @var Stringable[]|Field[][]|string[][]|string[]|null
     */
    protected ?array $rows = null;

    protected function renderContent(): string|Stringable
    {
        $this->action ??= Url::current();

        $this->attributes['id'] ??= $this->model ? Inflector::camel2id($this->model->formName()) : $this->getId();
        $this->attributes['hx-select'] ??= "#{$this->getId()}";
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];
        $this->attributes['hx-select-oob'] ??= '#flashes:beforeend';
        $this->attributes['hx-boost'] ??= "true";

        return Form::make()
            ->attributes($this->attributes)
            ->addClass('form')
            ->action($this->action)
            ->content($this->getContent());
    }

    protected function getContent(): string
    {
        return strtr($this->layout, [
            '{errors}' => $this->getErrors(),
            '{rows}' => $this->getRows(),
            '{buttons}' => $this->getButtons(),
            '{footer}' => $this->getFooter(),
        ]);
    }

    protected function getErrors(): string|Stringable
    {
        return ErrorSummary::make()
            ->title(false)
            ->models($this->model);
    }

    protected function getRows(): string|Stringable
    {
        return is_array(current($this->rows))
            ? implode('', array_map($this->getFieldset(...), $this->rows))
            : $this->getFieldset($this->rows);
    }

    protected function getFieldset(array $fields): Stringable
    {
        return Fieldset::make()
            ->form($this)
            ->model($this->model)
            ->fields(...$fields);
    }

    protected function getButtons(): ?Stringable
    {
        if (false === $this->buttons) {
            return null;
        }

        $content = $this->buttons
            ? Div::make()
                ->class('btn-group')
                ->content(...$this->buttons)
            : Div::make()
                ->content($this->getSubmitButton());

        $row = FormRow::make()
            ->content($content);

        return $this->hasStickyButtons
            ? Div::make()
                ->class('form-buttons')
                ->content($row)
            : $row;
    }

    protected function getSubmitButton(): Stringable
    {
        return Button::make()
            ->primary()
            ->type('submit')
            ->text($this->model instanceof ActiveRecordInterface && $this->model->getIsNewRecord()
                ? Yii::t('skeleton', 'Create')
                : Yii::t('skeleton', 'Update'));
    }

    protected function getFooter(): ?Stringable
    {
        if (false === $this->footer) {
            return null;
        }

        return FormFooter::make()
            ->model($this->model)
            ->items($this->footer);
    }
}