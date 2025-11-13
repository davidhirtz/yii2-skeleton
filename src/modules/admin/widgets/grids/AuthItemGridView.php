<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\html\Ul;
use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\columns\ButtonsColumn;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\MessageSourceTrait;
use Override;
use Yii;

class AuthItemGridView extends GridView
{
    use MessageSourceTrait;

    public ?User $user = null;

    /**
     * @var string|null the previous rule name, needs to be `public` because it's called in content closure.
     */
    public static ?string $prevRuleName = null;

    #[Override]
    public function init(): void
    {
        if ($this->user) {
            $this->rowAttributes = fn (AuthItem $authItem) => ($authItem->isAssigned || $authItem->isInherited)
                ? ['class' => 'is-selected']
                : null;
        }

        $this->columns ??= [
            $this->typeColumn(),
            $this->nameColumn(),
            $this->descriptionColumn(),
            $this->user ? $this->buttonsColumn() : $this->usersColumn(),
        ];

        parent::init();
    }

    protected function typeColumn(): array
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'content' => fn (AuthItem $authItem) => Html::icon($authItem->getTypeIcon())
                ->tooltip($authItem->getTypeName())
                ->render(),
        ];
    }

    protected function nameColumn(): array
    {
        return [
            'attribute' => 'name',
            'content' => function (AuthItem $authItem) {
                $cssClass = $authItem->isRole() ? 'strong' : null;

                if ($authItem->isPermission()) {
                    preg_match('/[A-Z]/', $authItem->name, $matches, PREG_OFFSET_CAPTURE);
                    $ruleName = substr($authItem->name, 0, $matches[0][1] ?? 0);

                    if ($ruleName !== static::$prevRuleName) {
                        static::$prevRuleName = $ruleName;
                        $cssClass = 'strong';
                    }
                }

                return Html::tag('span', $authItem->isRole() ? $authItem->getDisplayName() : ($this->getTranslations()[$authItem->description] ?? $authItem->description), [
                    'class' => $cssClass,
                ]);
            }
        ];
    }

    protected function descriptionColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Inherited Permissions'),
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => fn (AuthItem $authItem) => $this->getDescriptionFormItem($authItem),
        ];
    }

    protected function getDescriptionFormItem(AuthItem $authItem): string
    {
        $items = [];

        foreach ($authItem->children as $child) {
            $description = $this->getTranslations()[$child->description] ?? $child->description;
            $isActive = $this->user && !$authItem->isAssigned && ($child->isAssigned || $child->isInherited);

            $items[] = $isActive ? Html::tag('mark', $description) : $description;
        }


        return (string)Ul::make()
            ->class('list-unstyled')
            ->items(array_filter($items));
    }

    protected function usersColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Users'),
            'content' => fn (AuthItem $authItem) => $this->getUsersForItem($authItem),
        ];
    }

    protected function getUsersForItem(AuthItem $authItem): string
    {
        $items = array_map(
            fn (User $user) => Html::a($user->getUsername(), ['auth/view', 'user' => $user->id]),
            $authItem->users
        );

        return (string)Ul::make()
            ->class('list-unstyled')
            ->items($items);
    }

    protected function buttonsColumn(): array
    {
        return [
            'class' => ButtonsColumn::class,
            'content' => fn (AuthItem $authItem) => $this->getRowButtons($authItem),
        ];
    }

    /**
     * @see AuthController::actionAssign()
     * @see AuthController::actionRevoke()
     */
    protected function getRowButtons(AuthItem $authItem): string
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
