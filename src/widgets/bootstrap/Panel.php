<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\bootstrap;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\traits\TagAttributesTrait;
use davidhirtz\yii2\skeleton\html\traits\TagContentTrait;
use davidhirtz\yii2\skeleton\html\traits\TagIdTrait;
use davidhirtz\yii2\skeleton\widgets\traits\ContainerTrait;
use davidhirtz\yii2\skeleton\widgets\Widget;

class Panel extends Widget
{
    use ContainerConfigurationTrait;
    use ContainerTrait;
    use TagContentTrait;
    use TagIdTrait;

    final public const string TYPE_DEFAULT = 'default';
    final public const string TYPE_DANGER = 'danger';

    public ?string $title = null;
    public ?bool $collapse = null;
    public string $type = self::TYPE_DEFAULT;

    protected function renderContent(): string
    {
        $card = Card::make()
            ->title($this->title)
            ->content(...$this->content)
            ->collapsed($this->collapse);

        if ($this->type === self::TYPE_DANGER) {
            $card->danger();
        }

        return $card->render();
    }
}
