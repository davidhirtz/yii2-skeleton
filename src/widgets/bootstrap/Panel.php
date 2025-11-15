<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\widgets\Widget;

class Panel extends Widget
{
    final public const string TYPE_DEFAULT = 'default';
    final public const string TYPE_DANGER = 'danger';

    public ?string $id = null;
    public ?string $title = null;
    public ?string $content = null;
    public ?bool $collapse = null;
    public string $type = self::TYPE_DEFAULT;

    protected function render(): string
    {
        if (!$this->content) {
            return '';
        }
        return Container::make()
            ->attribute('id', $this->id)
            ->html($this->renderContent())
            ->render();
    }

    protected function renderContent(): string
    {
        $card = Card::make()
            ->title($this->title)
            ->html($this->content)
            ->collapsed($this->collapse);

        if ($this->type === self::TYPE_DANGER) {
            $card->danger();
        }

        return $card->render();
    }
}
