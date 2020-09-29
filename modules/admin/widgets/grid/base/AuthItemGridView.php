<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;


use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\grid\GridView;

/**
 * Class AuthItemGridView
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base
 */
class AuthItemGridView extends GridView
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var string[]
     */
    public $summaryOptions = [
        'class' => 'summary alert alert-info',
    ];

    /**
     * @var string[]
     */
    public $tableOptions = [
        'class' => 'table table-striped',
    ];

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->rowOptions) {
            if ($this->user) {
                $this->rowOptions = function (AuthItem $authItem) {
                    return ($authItem->isAssigned || $authItem->isInherited) ? ['class' => 'bg-success'] : null;
                };
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

    /**
     * @return array
     */
    public function typeColumn()
    {
        return [
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-center'],
            'content' => function (AuthItem $authItem) {
                return Icon::tag($authItem->getTypeIcon(), [
                    'data-toggle' => 'tooltip',
                    'title' => $authItem->getTypeName()
                ]);
            }
        ];
    }

    /**
     * @return array
     */
    public function nameColumn()
    {
        return [
            'attribute' => 'name',
            'content' => function (AuthItem $authItem) {
                return $authItem->isRole() ? Html::tag('strong', $authItem->getDisplayName()) : Yii::t('skeleton', $authItem->description);
            }
        ];
    }

    /**
     * @return array
     */
    public function descriptionColumn()
    {
        return [
            'label' => Yii::t('skeleton', 'Additional Permissions'),
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (AuthItem $authItem) {
                $items = [];

                foreach ($authItem->children as $child) {
                    $description = Yii::t('skeleton', $child->description);
                    $isActive = $this->user && !$authItem->isAssigned && ($child->isAssigned || $child->isInherited);
                    $items[] = $isActive ? Html::tag('span', $description, ['class' => 'bg-success']) : $description;
                }

                return Html::ul(array_filter($items), ['class' => 'list-unstyled', 'encode' => false]);
            }
        ];
    }

    /**
     * @return array
     */
    public function usersColumn()
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

    /**
     * @return array
     */
    public function buttonsColumn()
    {
        return [
            'contentOptions' => ['class' => 'text-right text-nowrap'],
            'content' => function (AuthItem $authItem) {
                return Html::buttons($this->getRowButtons($authItem));
            }
        ];
    }

    /**
     * @param AuthItem $authItem
     * @return array|string
     */
    protected function getRowButtons($authItem)
    {
        $route = [$authItem->isAssigned ? 'revoke' : 'assign', 'id' => $this->user->id, 'name' => $authItem->name, 'type' => $authItem->type];

        return Html::a(Icon::tag($authItem->isAssigned ? 'ban' : 'star'), $route, [
            'class' => 'btn btn-primary',
            'data-method' => 'post',
        ]);
    }
}