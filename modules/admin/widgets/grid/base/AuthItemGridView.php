<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;


use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\AuthItem;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use Yii;
use yii\grid\GridView;
use yii\i18n\MessageSource;

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
     * @var array
     */
    private $_translations;

    /**
     * @var string the previous rule name, needs to be `public` because it's called in content closure.
     */
    public static $prevRuleName;

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

    /**
     * @return array
     */
    public function descriptionColumn()
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

    /**
     * Finds the correct translation source for the authItem description.
     * @return array
     */
    public function getTranslations(): array
    {
        if ($this->_translations === null) {
            $this->_translations = [];

            if (Yii::$app->language !== Yii::$app->sourceLanguage) {
                $i18n = Yii::$app->getI18n();
                $sources = array_keys($i18n->translations);

                /** @var AuthItem $authItem */
                foreach ($this->dataProvider->getModels() as $authItem) {
                    /** @var MessageSource $source */
                    foreach ($sources as $source) {
                        if ($translation = $i18n->getMessageSource($source)->translate($source, $authItem->description, Yii::$app->language)) {
                            $this->_translations[$authItem->description] = $translation;
                        }
                    }
                }
            }
        }

        return $this->_translations;
    }

    /**
     * @param array $translations
     */
    public function setTranslations(array $translations): void
    {
        $this->_translations = $translations;
    }
}