<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Btn;
use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\MessageSourceTrait;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\grid\GridView;

class AuthItemGridView extends GridView
{
    use MessageSourceTrait;

    public ?User $user = null;

    public $summaryOptions = [
        'class' => 'summary alert alert-info',
    ];

    public $tableOptions = [
        'class' => 'table table-striped',
    ];

    /**
     * @var string|null the previous rule name, needs to be `public` because it's called in content closure.
     */
    public static ?string $prevRuleName = null;

    public function init(): void
    {
        if (!$this->rowOptions) {
            if ($this->user) {
                $this->rowOptions = fn (AuthItem $authItem) => ($authItem->isAssigned || $authItem->isInherited) ? ['class' => 'bg-success'] : null;
            }
        }

        if (!$this->columns) {
            $this->columns = [
                $this->typeColumn(),
                $this->nameColumn(),
                $this->descriptionColumn(),
            ];

            $this->columns[] = $this->user ? $this->buttonsColumn() : $this->usersColumn();
        }

        parent::init();
    }

    public function typeColumn(): array
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'content' => fn (AuthItem $authItem) => Icon::tag($authItem->getTypeIcon(), [
                'data-toggle' => 'tooltip',
                'title' => $authItem->getTypeName()
            ])
        ];
    }

    public function nameColumn(): array
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

    public function descriptionColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Inherited Permissions'),
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (AuthItem $authItem) {
                $items = [];

                foreach ($authItem->children as $child) {
                    $description = $this->getTranslations()[$child->description] ?? $child->description;
                    $isActive = $this->user && !$authItem->isAssigned && ($child->isAssigned || $child->isInherited);
                    $items[] = $isActive ? Html::tag('span', $description, ['class' => 'bg-success']) : $description;
                }

                return Html::ul(array_filter($items), ['class' => 'list-unstyled', 'encode' => false]);
            }
        ];
    }

    public function usersColumn(): array
    {
        return [
            'label' => Yii::t('skeleton', 'Users'),
            'content' => function (AuthItem $authItem) {
                $items = [];

                foreach ($authItem->users as $user) {
                    $items[$user->id] = Html::a($user->getUsername(), ['auth/view', 'user' => $user->id]);
                }

                return Html::ul(array_filter($items), ['class' => 'list-unstyled', 'encode' => false]);
            }
        ];
    }

    public function buttonsColumn(): array
    {
        return [
            'contentOptions' => ['class' => 'text-end text-nowrap'],
            'content' => fn (AuthItem $authItem): string => Html::buttons($this->getRowButtons($authItem))
        ];
    }

    /**
     * @see AuthController::actionAssign()
     * @see AuthController::actionRevoke()
     */
    protected function getRowButtons(AuthItem $authItem): array|string
    {
        $route = [$authItem->isAssigned ? 'revoke' : 'assign', 'id' => $this->user->id, 'name' => $authItem->name, 'type' => $authItem->type];

        return Btn::primary()
            ->icon($authItem->isAssigned ? 'ban' : 'star')
            ->post($route)
            ->render();
    }
}
