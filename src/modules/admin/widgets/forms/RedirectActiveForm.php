<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\forms;

use Hirtz\Skeleton\models\Redirect;
use Hirtz\Skeleton\widgets\forms\ActiveForm;
use Override;

/**
 * @property Redirect $model
 */
class RedirectActiveForm extends ActiveForm
{
    #[Override]
    protected function configure(): void
    {
        $this->rows ??= [
            'type',
            'request_uri',
            'url',
        ];

        parent::configure();
    }
}
