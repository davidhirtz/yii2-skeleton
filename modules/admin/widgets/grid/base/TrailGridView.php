<?php

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grid\base;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\GridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\MessageSourceTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TypeGridViewTrait;
use davidhirtz\yii2\timeago\Timeago;
use Yii;

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
    private $_modelNames;

    /**
     * @var string[]
     */
    public $tableOptions = [
        'class' => 'table table-striped',
    ];

    public function init()
    {
        if (!$this->columns) {
            $this->columns = [
                $this->typeColumn(),
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
    public function modelColumn()
    {
        return [
            'attribute' => 'model',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'visible' => !$this->dataProvider->model,
            'content' => function (Trail $trail) {
                return $trail->model ? Html::a($this->getModelName($trail), ['index', 'model' => $trail->model, 'model_id' => $trail->model_id]) : '';
            }
        ];
    }

    /**
     * @return array
     */
    public function dataColumn()
    {
        return [
            'attribute' => 'data',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (Trail $trail) {
                if ($trail->message) {
                    return $this->getTranslations()[$trail->message] ?? $trail->message;
                }

                $rows = '';

                if (is_array($trail->data)) {
                    foreach ($trail->data as $attributeName => $values) {
                        $rows .= '<tr><td class="strong">' . $trail->getModelClass()->getAttributeLabel($attributeName) . '</td>' .
                            '<td>' . (is_array($values[0]) ? ('<pre>' . print_r($values[0], 1) . '</pre>') : $values[0]) . '</td>' .
                            '<td>' . (is_array($values[1]) ? ('<pre>' . print_r($values[0], 1) . '</pre>') : $values[1]) . '</td></tr>';
                    }
                }

                return $rows ? "<table>{$rows}</table>" : '';
            }
        ];
    }

    /**
     * @return array
     */
    public function userColumn()
    {
        return [
            'attribute' => 'user_id',
            'headerOptions' => ['class' => 'd-none d-md-table-cell'],
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (Trail $trail) {
                if (!$trail->user_id) {
                    return '';
                }

                if ($trail->user) {
                    return Html::username($trail->user, ['/admin/user/update', 'id' => $trail->user_id]);
                }

                $name = Html::tag('em', Yii::t('yii', 'Deleted ({id})', ['id' => $trail->user_id]));
                return Html::a($name, ['index', 'model' => User::class, 'model_id' => $trail->user_id]);
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
            'contentOptions' => ['class' => 'd-none d-md-table-cell'],
            'content' => function (Trail $trail) {
                return Timeago::tag($trail->created_at);
            }
        ];
    }

    /**
     * @param Trail $trail
     * @return string
     */
    protected function getModelName($trail)
    {
        if (!isset($this->_modelNames[$trail->model][$trail->model_id])) {
            $modelClass = $trail->getModelClass();
            $tag = $modelClass instanceof ActiveRecord && !$modelClass->getIsNewRecord() ? 'strong' : 'em';
            $this->_modelNames[$trail->model][$trail->model_id] = Html::tag($tag, $trail->getModelName());
        }

        return $this->_modelNames[$trail->model][$trail->model_id];
    }
}