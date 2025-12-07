<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\models\interfaces\TypeAttributeInterface;
use davidhirtz\yii2\skeleton\models\UserLogin;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\LinkColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\RelativeTimeColumn;
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
    public function configure(): void
    {
        $this->columns ??= [
            $this->getTypeIconColumn(),
            LinkColumn::make()
                ->property('ip_address')
                ->url(fn (UserLogin $login) => ['view', 'id' => $login->id]),
            LinkColumn::make()
                ->property('user')
                ->visible(!$this->user)
                ->content(fn (UserLogin $login): Stringable => Username::make()
                    ->user($login->user))
                ->url(fn (UserLogin $login): array => ['view', 'user' => $login->user_id]),
            DataColumn::make()
                ->property('browser')
                ->hiddenForSmallDevices(),
            RelativeTimeColumn::make()
                ->property('created_at'),
        ];

        parent::configure();
    }

    protected function getTypeIcon(TypeAttributeInterface $model): string
    {
        /** @var UserLogin $model */
        return $model->getTypeIcon() ?: "brand:$model->type";
    }
}
