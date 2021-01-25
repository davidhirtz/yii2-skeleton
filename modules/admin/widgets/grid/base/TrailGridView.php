<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\MessageSourceTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
use Jfcherng\Diff\DiffHelper;
use Yii;
use yii\helpers\Url;

/**
 * Class TrailGridView
 * @package davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base
 *
 * @property TrailActiveDataProvider $dataProvider
 */
class TrailGridView extends GridView
{
    use MessageSourceTrait;
    use TypeGridViewTrait;

    /**
     * @var array
     */
    private $_trailModels;

    /**
     * @var string[]
     */
    public $tableOptions = [
        'class' => 'table table-striped trail',
    ];

    public function init()
    {
        $this->rowOptions = function (Trail $trail) {
            return [
                'id' => 'trail-' . $trail->id,
                'class' => $trail->isDeleteType() ? 'bg-danger' : '',
            ];
        };

        if (!$this->columns) {
            $this->columns = [
                $this->typeIconColumn(),
                $this->modelColumn(),
                $this->dataColumn(),
                $this->userColumn(),
                $this->createdAtColumn(),
            ];
        }

        $this->messageSourceAttribute = 'message';

        parent::init();
    }

    /**
     * @return array
     */
    public function modelColumn(): array
    {
        return [
            'attribute' => 'model',
            'contentOptions' => ['style' => 'width:300px'],
            'visible' => !$this->dataProvider->model,
            'content' => function (Trail $trail) {
                if ($trail->model) {
                    $model = $trail->getModelClass();
                    $isModel = $model instanceof ActiveRecord && !$model->getIsNewRecord();
                    $tag = $isModel ? 'strong' : 'em';
                    $type = $isModel ? $trail->getModelType() : false;

                    return Html::a(Html::tag($tag, $trail->getModelName()), $this->getTrailModelRoute($trail)) .
                        ($type ? Html::tag('div', $type, ['class' => 'small']) : '');
                }

                return '';
            }
        ];
    }

    /**
     * @return array
     */
    public function dataColumn(): array
    {
        return [
            'attribute' => 'data',
            'content' => [$this, 'dataColumnContent'],
        ];
    }

    /**
     * @param Trail $trail
     * @return mixed|string
     */
    public function dataColumnContent($trail)
    {
        if ($trail->isAuthPermissionType()) {
            return $this->renderAuthPermissionContent($trail);
        }

        if ($trail->hasAttributesEnabled()) {
            if ($trail->isCreateType()) {
                return $this->renderCreateAttributesContent($trail);
            }

            if ($trail->isUpdateType()) {
                return $this->renderUpdateAttributesContent($trail);
            }
        }

        if ($trail->hasDataModelEnabled()) {
            return $this->renderDataModelContent($trail);
        }

        return $this->renderMessageContent($trail);
    }

    /**
     * @param Trail $trail
     * @return string
     */
    protected function renderAuthPermissionContent($trail)
    {
        $params = [
            'permission' => Html::tag($trail->isAuthPermissionAssignType() ? 'ins' : 'del', $this->getTranslations()[$trail->message] ?? $trail->message),
        ];

        return $trail->isAuthPermissionAssignType() ? Yii::t('skeleton', 'Permission {permission} assigned', $params) :
            Yii::t('skeleton', 'Permission {permission} revoked', $params);
    }

    /**
     * @param Trail $trail
     * @return string
     */
    protected function renderCreateAttributesContent($trail)
    {
        $model = $trail->getModelClass();
        $rows = [];

        if (is_array($trail->data)) {
            foreach ($trail->data as $attribute => $value) {
                if ($value = $this->formatTrailAttributeValue($model, $attribute, $value)) {
                    $rows[] = [
                        $model->getAttributeLabel($attribute),
                        Html::encode($value),
                    ];
                }
            }
        }

        return $this->renderTrailAttributes($rows, ['class' => 'trail-insert']);
    }

    /**
     * @param Trail $trail
     * @return string
     */
    protected function renderUpdateAttributesContent($trail)
    {
        $model = $trail->getModelClass();
        $rows = [];

        if (is_array($trail->data)) {
            foreach ($trail->data as $attribute => $values) {
                $oldValue = $this->formatTrailAttributeValue($model, $attribute, $values[0]);
                $newValue = $this->formatTrailAttributeValue($model, $attribute, $values[1]);

                if ($oldValue !== $newValue) {
                    $rows[] = [
                        $model->getAttributeLabel($attribute),
                        DiffHelper::calculate($oldValue, $newValue, 'SideBySide', [], [
                            'showHeader' => false,
                            'lineNumbers' => false,
                        ]),
                    ];
                }
            }
        }

        return $this->renderTrailAttributes($rows, ['class' => 'trail-update']);
    }

    /**
     * @param array $rows
     * @param array $options
     * @return string
     */
    protected function renderTrailAttributes($rows, $options = []): string
    {
        Html::addCssClass($options, 'trail-table');
        return $rows ? Html::tag('table', Html::tableBody($rows), $options) : '';
    }

    /**
     * @param Trail $trail
     * @return string
     */
    protected function renderDataModelContent($trail)
    {
        return $this->renderI18nTrailMessage($trail, $trail->getDataModelClass());
    }

    /**
     * @param Trail $trail
     * @return string
     */
    protected function renderMessageContent($trail)
    {
        if ($trail->message) {
            return trim(($this->getTranslations()[$trail->message] ?? $trail->message) . ' ' . $this->renderDataTrailLink($trail));
        }

        return $this->renderI18nTrailMessage($trail, $trail->getModelClass());
    }

    /**
     * @param Trail $trail
     * @param ActiveRecord|TrailBehavior $model
     * @return string
     */
    protected function renderI18nTrailMessage($trail, $model)
    {
        if ($model) {
            $name = $model->getTrailModelName();
            $model = ($route = $model->getTrailModelAdminRoute()) ? Html::a($name, $route) : $name;
        } else {
            $model = Html::tag('em', Yii::t('skeleton', 'Deleted'));
        }

        $options = $trail->getTypeOptions();
        $message = '';

        if (isset($options['message'])) {
            $message .= Yii::t($options['messageCategory'] ?? 'skeleton', $options['message'] ?? '', [
                'model' => $model,
            ]);
        }

        return trim($message . ' ' . $this->renderDataTrailLink($trail));
    }

    /**
     * @return array
     */
    public function userColumn()
    {
        return [
            'attribute' => 'user_id',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
            'visible' => !$this->dataProvider->user,
            'content' => function (Trail $trail) {
                if (!$trail->user_id) {
                    return '';
                }

                if ($trail->user) {
                    return Html::username($trail->user, ['index', 'user' => $trail->user_id]);
                }

                $name = Yii::t('skeleton', '{model} #{id}', [
                    'model' => Yii::t('skeleton', 'User'),
                    'id' => $trail->user_id,
                ]);

                return Html::a($name, ['index', 'model' => User::class . ':' . $trail->user_id]);
            }
        ];
    }

    /**
     * @return array
     */
    public function createdAtColumn()
    {
        return [
            'attribute' => 'created_at',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
            'content' => function (Trail $trail) {
                return Timeago::tag($trail->created_at);
            }
        ];
    }

    /**
     * @param Trail $trail
     * @return string
     */
    protected function renderDataTrailLink($trail)
    {
        if ($trailId = ($trail->data['trail_id'] ?? false)) {
            $link = isset($this->dataProvider->getModels()[$trailId]) ? Url::current(['#' => 'trail-' . $trailId]) : ['index', 'id' => $trailId];
            return Html::a('(#' . $trailId . ')', $link);
        }

        return '';
    }

    /**
     * Wraps behavior method and makes sure value is cast to string to prevent {@link \Jfcherng\Diff\Differ} to throw
     * errors.
     *
     * @param ActiveRecord $model
     * @param string $attribute
     * @param string $value
     * @return string
     */
    protected function formatTrailAttributeValue($model, $attribute, $value)
    {
        /** @var TrailBehavior $model */
        return (string)$model->formatTrailAttributeValue($attribute, $value);
    }

    /**
     * @param Trail $model
     * @param array $params
     * @return array
     */
    protected function getRoute($model, $params = [])
    {
        return ['index', 'id' => $model->id];
    }

    /**
     * @param Trail $trail
     * @return array|null
     */
    protected function getTrailModelRoute($trail)
    {
        return ['index', 'model' => implode(':', array_filter([$trail->model, $trail->model_id]))];
    }
}