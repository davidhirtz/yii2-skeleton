<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Forms;

use Hirtz\Skeleton\Models\Forms\McpTokenForm;
use Hirtz\Skeleton\Modules\Admin\Controllers\McpTokenController;
use Hirtz\Skeleton\Widgets\Forms\ActiveForm;
use Hirtz\Skeleton\Widgets\Forms\Fields\CheckboxField;
use Hirtz\Skeleton\Widgets\Forms\Fields\SelectField;
use Override;
use Stringable;
use Yii;

/**
 * @see McpTokenController::actionCreate()
 *
 * @property McpTokenForm $model
 */
class McpTokenActiveForm extends ActiveForm
{
    #[Override]
    protected function configure(): void
    {
        $this->submitButtonText ??= Yii::t('skeleton', 'MCP_TOKEN_BUTTON_CREATE');
        parent::configure();
    }

    #[Override]
    protected function getDefaultRows(): array
    {
        return [
            [
                $this->getExpiresField(),
                $this->getReadOnlyField(),
            ],
        ];
    }

    protected function getExpiresField(): string|Stringable
    {
        return SelectField::make()
            ->property('expires')
            ->prompt(Yii::t('skeleton', 'MCP_TOKEN_EXPIRES_NEVER'))
            ->items($this->model->getExpiresItems());
    }

    protected function getReadOnlyField(): string|Stringable
    {
        return CheckboxField::make()
            ->property('readOnly');
    }
}
