<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\widgets\grids;

use Hirtz\Skeleton\models\interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\models\UserLogin;
use Hirtz\Skeleton\widgets\grids\columns\DataColumn;
use Hirtz\Skeleton\widgets\grids\columns\LinkColumn;
use Hirtz\Skeleton\widgets\grids\columns\RelativeTimeColumn;
use Hirtz\Skeleton\widgets\grids\GridView;
use Hirtz\Skeleton\widgets\grids\traits\TypeGridViewTrait;
use Hirtz\Skeleton\widgets\traits\UserWidgetTrait;
use Hirtz\Skeleton\widgets\Username;
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
