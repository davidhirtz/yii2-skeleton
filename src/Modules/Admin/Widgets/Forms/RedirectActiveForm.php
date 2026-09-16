<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Override;

/**
 * @property Redirect $model
 */
class RedirectActiveForm extends ActiveForm
{
    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            'type',
            'request_uri',
            'url',
        ];
    }
}
