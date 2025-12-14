<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Models\Interfaces\TypeAttributeInterface;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\LinkColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\TypeGridViewTrait;
use Hirtz\Skeleton\Widgets\Traits\UserWidgetTrait;
use Hirtz\Skeleton\Widgets\Username;
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
