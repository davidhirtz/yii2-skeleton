<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Form;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Hirtz\Skeleton\Widgets\Forms\Fields\InputField;
use Hirtz\Skeleton\Widgets\Forms\Fields\LinkField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * The form TinyMCE's link button opens in a modal; `includes/linkDialog.ts` reads it on submit, nothing posts it.
 */
class LinkDialog extends Widget
{
    final public const string ID = 'link-dialog';

    protected ?LinkField $field = null;
    protected string $href = '';
    protected string $text = '';
    protected string $label = '';
    protected ?string $labelPlaceholder = null;
    protected bool $blank = false;
    protected string $cssClass = '';

    /**
     * @var array<string, string>
     */
    protected array $classes = [];

    public function field(?LinkField $field): static
    {
        $this->field = $field;
        return $this;
    }

    public function href(?string $href): static
    {
        $this->href = (string)$href;
        return $this;
    }

    public function text(?string $text): static
    {
        $this->text = (string)$text;
        return $this;
    }

    /**
     * The `aria-label`, which screen readers announce in place of the text.
     */
    public function label(?string $label): static
    {
        $this->label = (string)$label;
        return $this;
    }

    /**
     * The label the link gets without one, such as the cms's for an entry link.
     */
    public function labelPlaceholder(?string $labelPlaceholder): static
    {
        $this->labelPlaceholder = $labelPlaceholder;
        return $this;
    }

    /**
     * @param array<string, string> $classes
     */
    public function classes(array $classes): static
    {
        $this->classes = $classes;
        return $this;
    }

    public function cssClass(?string $cssClass): static
    {
        $this->cssClass = (string)$cssClass;
        return $this;
    }

    public function blank(bool $blank = true): static
    {
        $this->blank = $blank;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Form::make()
            ->method('get')
            ->attribute('id', self::ID)
            ->attribute('data-link-dialog', true)
            ->attribute('hx-boost', 'false')
            ->addClass('form link-dialog')
            ->content(
                Div::make()
                    ->class('form-rows')
                    ->content(
                        $this->getHrefField(),
                        InputField::make()
                            ->attribute('id', self::ID . '-text')
                            ->attribute('name', 'text')
                            ->attribute('value', $this->text)
                            ->label(Yii::t('skeleton', 'LINK_TEXT_LABEL')),
                        InputField::make()
                            ->attribute('id', self::ID . '-label')
                            ->attribute('name', 'label')
                            ->attribute('value', $this->label)
                            ->placeholder($this->labelPlaceholder)
                            ->label(Yii::t('skeleton', 'LINK_ARIA_LABEL'))
                            ->hint(Yii::t('skeleton', 'LINK_ARIA_HINT')),
                        $this->classes ? SelectField::make()
                            ->attribute('id', self::ID . '-class')
                            ->attribute('name', 'class')
                            ->attribute('value', $this->cssClass)
                            ->items($this->classes)
                            ->label(Yii::t('skeleton', 'LINK_CLASS_LABEL')) : null,
                        CheckboxField::make()
                            ->attribute('id', self::ID . '-target')
                            ->attribute('name', 'target')
                            ->attribute('value', $this->blank ? '_blank' : '')
                            ->checkedValue('_blank')
                            ->label(Yii::t('skeleton', 'LINK_TARGET_LABEL')),
                    ),
                Div::make()
                    ->class('link-dialog-buttons')
                    ->content(
                        Button::make()
                            ->secondary()
                            ->type('button')
                            ->attribute('data-modal', '')
                            ->text(Yii::t('skeleton', 'MODAL_CANCEL_BUTTON')),
                        Button::make()
                            ->primary()
                            ->type('submit')
                            ->text(Yii::t('skeleton', 'LINK_SAVE_BUTTON')),
                    ),
            );
    }

    protected function getHrefField(): LinkField
    {
        return ($this->field ?? LinkField::make())
            ->attribute('id', self::ID . '-href')
            ->attribute('name', 'href')
            ->attribute('value', $this->href)
            ->attribute('autofocus', true)
            ->label(Yii::t('skeleton', 'LINK_URL_LABEL'));
    }
}
