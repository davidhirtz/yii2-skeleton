<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\LinkColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\traits\UserWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Username;
use Override;
use Stringable;

/**
 * @extends GridView<UserLogin>
 */
class UserLoginGridView extends GridView
{
    use UserWidgetTrait;
    use TypeGridViewTrait;

    #[Override]
    public function renderContent(): string|Stringable
    {
        $this->columns ??= [
            $this->getTypeIconColumn(),
            LinkColumn::make()
                ->property('ip_address')
                ->href(fn (UserLogin $login) => ['view', 'id' => $login->id]),
            LinkColumn::make()
                ->property('user')
                ->visible(!$this->user)
                ->content(fn (UserLogin $login): Stringable => Username::make()
                    ->user($login->user))
                ->href(fn (UserLogin $login): array => ['view', 'user' => $login->user_id]),
            DataColumn::make()
                ->property('browser')
                ->hiddenForSmallDevices(),
            TimeagoColumn::make()
                ->property('created_at'),
        ];

        return parent::renderContent();
    }

    /**
     * @param UserLogin $model
     */
    protected function getTypeIcon(TypeAttributeInterface $model): string
    {
        return $model->getTypeIcon() ?: "brand:$model->type";
    }
}
