<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use Override;
use Stringable;
use yii\base\Model;

/**
 * @extends DeleteButton<Model>
 */
class DeleteGridButton extends DeleteButton
{
    #[Override]
    protected function getButton(): Stringable
    {
        return Button::make()
            ->danger()
            ->ariaLabel($this->label)
            ->icon($this->icon)
            ->modal($this->getModal());
    }
}
