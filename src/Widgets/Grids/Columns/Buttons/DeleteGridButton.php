<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns\Buttons;

use Hirtz\Skeleton\Widgets\Buttons\DeleteButton;
use yii\base\Model;

/**
 * @extends DeleteButton<Model>
 */
class DeleteGridButton extends DeleteButton
{
    protected bool $iconOnly = true;
}
