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
use yii\web\Controller;

class ActiveForm extends Widget
{
    use TagAttributesTrait;
    use TagIdTrait;
    use ModelWidgetTrait;

    public array|string|false|null $action = null;

    public bool $hasStickyButtons = true;
    public string $layout = "{errors}{rows}{buttons}{footer}";

    protected array|false|null $buttons = null;
    protected ?string $submitButtonText = null;
    protected array|false|null $footer = null;
    protected array $excludedErrorProperties = [];

    /**
     * @var Stringable[]|Field[][]|string[][]|string[]|null
     */
    protected ?array $rows = null;

    public function action(string|false|null $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function rows(array|false|null $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->action ??= Yii::$app->controller instanceof Controller ? Url::current() : null;

        $this->attributes['id'] ??= $this->model ? Inflector::camel2id($this->model->formName()) : $this->getId();
        $this->attributes['hx-select'] ??= "#{$this->getId()}";
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];
        $this->attributes['hx-boost'] ??= "true";

        // @todo change this to exclude I18N attributes
        $this->rows ??= $this->model?->safeAttributes();
    }

    protected function renderContent(): string|Stringable
    {
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
            ->models($this->model)
            ->excluding($this->excludedErrorProperties);
    }

    protected function getRows(): string|Stringable
    {
        $content = is_array(current($this->rows))
            ? implode('', array_map($this->getFieldset(...), $this->rows))
            : $this->getFieldset($this->rows);

        return $content
            ? Div::make()
                ->class('form-rows')
                ->content($content)
            : '';
    }

    protected function getFieldset(array|Fieldset $fieldsetOrRows): ?Stringable
    {
        if (!$fieldsetOrRows instanceof Fieldset) {
            $fieldsetOrRows = Fieldset::make()
                ->rows($fieldsetOrRows);
        }

        return $fieldsetOrRows
            ->form($this);
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

        return Div::make()
            ->class('form-buttons')
            ->addClass($this->hasStickyButtons ? 'sticky' : '')
            ->content($row);
    }

    protected function getSubmitButton(): Stringable
    {
        $this->submitButtonText ??= $this->model instanceof ActiveRecordInterface && $this->model->getIsNewRecord()
            ? Yii::t('skeleton', 'Create')
            : Yii::t('skeleton', 'Update');

        return Button::make()
            ->primary()
            ->text($this->submitButtonText)
            ->type('submit');
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
