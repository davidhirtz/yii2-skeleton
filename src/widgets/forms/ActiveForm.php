<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms;

use davidhirtz\yii2\skeleton\helpers\Url;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\html\Form;
use davidhirtz\yii2\skeleton\html\Li;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\forms\footers\CreatedAtFooterItem;
use davidhirtz\yii2\skeleton\widgets\forms\footers\FormFooter;
use davidhirtz\yii2\skeleton\widgets\forms\footers\UpdatedAtFooterItem;
use davidhirtz\yii2\skeleton\widgets\forms\rows\FormRow;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Username;
use davidhirtz\yii2\skeleton\widgets\Widget;
use davidhirtz\yii2\timeago\Timeago;
use Stringable;
use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;

class ActiveForm extends Widget
{
    use TagAttributesTrait;
    use TagIdTrait;
    use ModelWidgetTrait;

    public array|string|false|null $action = null;

    public bool $hasStickyButtons = true;
    public string $layout = "{fields}{buttons}{footer}";

    protected array|false|null $buttons = null;
    protected array|false|null $footer = null;

    /**
     * @var ActiveFieldNew[]|string[]|null
     */
    protected ?array $fields = null;

    protected function renderContent(): string|Stringable
    {
        $this->action ??= Url::current();

        $this->attributes['hx-select'] ??= "#{$this->getId()}";
        $this->attributes['hx-target'] ??= $this->attributes['hx-select'];
        $this->attributes['hx-select-oob'] ??= '#flashes:beforeend';
        $this->attributes['hx-boost'] ??= true;

        return Form::make()
            ->attributes($this->attributes)
            ->action($this->action)
            ->content($this->getContent());
    }

    protected function getContent(): string
    {
        return strtr($this->layout, [
            '{errors}' => $this->getErrors(),
            '{fields}' => $this->getFields(),
            '{buttons}' => $this->getButtons(),
            '{footer}' => $this->getFooter(),
        ]);
    }

    protected function getErrors(): string|Stringable
    {
        return ErrorSummary::make()
            ->models($this->model);
    }

    protected function getFields(): string|Stringable
    {
        return Fieldset::make()
            ->form($this)
            ->model($this->model)
            ->fields(...$this->fields);
    }

    protected function getButtons(): ?Stringable
    {
        if (false === $this->buttons) {
            return null;
        }

        $content = $this->buttons
            ? Div::make()
                ->class('btn-toolbar')
                ->content(...$this->buttons)
            : $this->getSubmitButton();

        $row = FormRow::make()
            ->content($content);

        return $this->hasStickyButtons
            ? Div::make()
                ->class('form-group-sticky')
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