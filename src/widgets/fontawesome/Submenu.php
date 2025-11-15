<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\fontawesome;

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\widgets\navs\Header;
use Override;
use Stringable;
use yii\helpers\Html;

class Submenu extends Nav
{
    public ?string $title = null;

    public array $badgeOptions = ['class' => 'badge d-none d-md-inline-block'];
    public array $cssClass = ['submenu', 'nav', 'nav-pills'];
    public array $labelOptions = ['class' => 'd-none d-md-inline'];

    #[Override]
    public function init(): void
    {
        Html::addCssClass($this->options, $this->cssClass);
        parent::init();
    }

    #[Override]
    public function run(): string
    {
        $html = $this->renderTitle() . $this->renderItems();
        return $html ? Container::make()->content($html)->render() : '';
    }

    protected function renderTitle(): Stringable
    {
        return Header::make()
            ->title($this->title);
    }
}
