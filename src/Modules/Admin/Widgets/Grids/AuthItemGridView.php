<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Html\Button;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Icon;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Models\AuthItem;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AuthController;
use Hirtz\Skeleton\Widgets\Grids\Columns\ButtonColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\Column;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\MessageSourceTrait;
use Hirtz\Skeleton\Widgets\Traits\UserWidgetTrait;
use Override;
use Stringable;
use Yii;

class AuthItemGridView extends GridView
{
    use MessageSourceTrait;
    use UserWidgetTrait;

    /**
     * @var string|null the previous rule name, needs to be `public` because it's called in content closure.
     */
    public static ?string $prevRuleName = null;

    #[Override]
    public function configure(): void
    {
        if ($this->user) {
            $this->rowAttributes = fn (AuthItem $authItem) => ($authItem->isAssigned || $authItem->isInherited)
                ? ['class' => 'is-selected']
                : null;
        }

        $this->columns ??= [
            $this->getTypeColumn(),
            $this->getNameColumn(),
            $this->getDescriptionColumn(),
            $this->user ? $this->getButtonColumn() : $this->getUsersColumn(),
        ];

        parent::configure();
    }

    protected function getTypeColumn(): Column
    {
        return Column::make()
            ->content($this->getTypeColumnContent(...))
            ->hiddenForSmallDevices()
            ->centered();
    }

    protected function getTypeColumnContent(AuthItem $authItem): Stringable
    {
        return Icon::make()->name($authItem->getTypeIcon())
            ->tooltip($authItem->getTypeName());
    }

    protected function getNameColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('name')
            ->content($this->getNameColumnContent(...));
    }

    protected function getNameColumnContent(AuthItem $authItem): Stringable
    {
        $cssClass = $authItem->isRole() ? 'strong' : null;

        if ($authItem->isPermission()) {
            preg_match('/[A-Z]/', $authItem->name, $matches, PREG_OFFSET_CAPTURE);
            $ruleName = substr($authItem->name, 0, $matches[0][1] ?? 0);

            if ($ruleName !== static::$prevRuleName) {
                static::$prevRuleName = $ruleName;
                $cssClass = 'strong';
            }
        }

        return Div::make()
            ->class($cssClass)
            ->content($authItem->isRole()
                ? $authItem->getDisplayName()
                : ($this->getTranslations()[$authItem->description] ?? $authItem->description));
    }

    protected function getDescriptionColumn(): Column
    {
        return Column::make()
            ->header(Yii::t('skeleton', 'Inherited Permissions'))
            ->content($this->getDescriptionColumnContent(...))
            ->hiddenForSmallDevices();
    }

    protected function getDescriptionColumnContent(AuthItem $authItem): Stringable
    {
        $items = [];

        foreach ($authItem->children as $child) {
            $description = $this->getTranslations()[$child->description] ?? $child->description;
            $isActive = $this->user && !$authItem->isAssigned && ($child->isAssigned || $child->isInherited);

            $items[] = $isActive ? Html::tag('mark', $description) : $description;
        }


        return Ul::make()
            ->items(...array_filter($items));
    }

    protected function getUsersColumn(): Column
    {
        return Column::make()
            ->header(Yii::t('skeleton', 'Users'))
            ->content($this->getUsersColumnColumn(...));
    }

    protected function getUsersColumnColumn(AuthItem $authItem): Stringable
    {
        $items = array_map(
            fn (User $user) => Html::a($user->getUsername(), ['auth/view', 'user' => $user->id]),
            $authItem->users
        );

        return Ul::make()
            ->class('list-unstyled')
            ->items(...$items);
    }

    protected function getButtonColumn(): ?Column
    {
        return ButtonColumn::make()
            ->content($this->getButtonColumnContent(...));
    }

    /**
     * @see AuthController::actionAssign()
     * @see AuthController::actionRevoke()
     */
    protected function getButtonColumnContent(AuthItem $authItem): string
    {
        $route = [
            $authItem->isAssigned ? 'revoke' : 'assign',
            'id' => $this->user->id,
            'name' => $authItem->name,
            'type' => $authItem->type,
        ];

        $tooltip = $authItem->isAssigned
            ? Yii::t('skeleton', 'Revoke {auth}', ['auth' => $authItem->getTypeName()])
            : Yii::t('skeleton', 'Assign {auth}', ['auth' => $authItem->getTypeName()]);

        return Button::make()
            ->primary()
            ->icon($authItem->isAssigned ? 'ban' : 'star')
            ->tooltip($tooltip)
            ->replace($route, '#' . $this->getId())
            ->render();
    }
}
