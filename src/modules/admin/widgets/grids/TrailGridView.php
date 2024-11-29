<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\behaviors\TrailBehavior;
use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\MessageSourceTrait;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\timeago\TimeagoColumn;
use Jfcherng\Diff\DiffHelper;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\helpers\Url;

/**
 * @extends GridView<Trail>
 * @property TrailActiveDataProvider $dataProvider
 */
class TrailGridView extends GridView
{
    use MessageSourceTrait;
    use TypeGridViewTrait;

    public $tableOptions = [
        'class' => 'table table-striped trail',
    ];

    public function init(): void
    {
        $this->rowOptions = fn (Trail $trail) => [
            'id' => 'trail-' . $trail->id,
            'class' => $trail->isDeleteType() ? 'bg-danger' : '',
        ];

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


    public function modelColumn(): array
    {
        return [
            'attribute' => 'model',
            'contentOptions' => ['style' => 'width:300px'],
            'visible' => !$this->dataProvider->model,
            'content' => function (Trail $trail): string {
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

    public function dataColumn(): array
    {
        return [
            'attribute' => 'data',
            'content' => $this->dataColumnContent(...),
        ];
    }

    public function dataColumnContent(Trail $trail): string
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

    protected function renderAuthPermissionContent(Trail $trail): string
    {
        $params = [
            'permission' => Html::tag($trail->isAuthPermissionAssignType() ? 'ins' : 'del', $this->getTranslations()[$trail->message] ?? $trail->message),
        ];

        return $trail->isAuthPermissionAssignType() ? Yii::t('skeleton', 'Permission {permission} assigned', $params) :
            Yii::t('skeleton', 'Permission {permission} revoked', $params);
    }

    protected function renderCreateAttributesContent(Trail $trail): string
    {
        $model = $trail->getModelClass();
        $rows = [];

        if (is_array($trail->data)) {
            foreach ($trail->data as $attribute => $value) {
                if ($model) {
                    $value = $this->formatTrailAttributeValue($model, $attribute, $value);
                    $attribute = $model->getAttributeLabel($attribute);
                }

                if ($value) {
                    $rows[] = [$attribute, $this->renderCreatedAttributeValue($value)];
                }
            }
        }

        return $this->renderTrailAttributes($rows, ['class' => 'trail-insert']);
    }

    protected function renderCreatedAttributeValue(mixed $value): string
    {
        if ($value instanceof ActiveRecord) {
            return $this->renderTrailActiveRecordAttribute($value);
        }

        return is_array($value) ? Html::ul($value, ['class' => 'list-unstyled']) : Html::encode($value);
    }

    protected function renderUpdateAttributesContent(Trail $trail): string
    {
        $model = $trail->getModelClass();
        $rows = [];

        if (is_array($trail->data)) {
            foreach ($trail->data as $attribute => $values) {
                if ($model) {
                    $values = array_map(fn ($value) => $this->formatTrailAttributeValue($model, $attribute, $value), $values);
                    $attribute = $model->getAttributeLabel($attribute);
                }

                if ($values[0] !== $values[1]) {
                    $rows[] = [$attribute, $this->renderUpdatedAttributeValues($values[0], $values[1])];
                }
            }
        }

        return $this->renderTrailAttributes($rows, ['class' => 'trail-update']);
    }

    protected function renderUpdatedAttributeValues(mixed $oldValue, mixed $newValue): string
    {
        if ($oldValue instanceof ActiveRecord || $newValue instanceof ActiveRecord) {
            $cells = [
                Html::tag('td', $this->renderTrailActiveRecordAttribute($oldValue), ['class' => 'old']),
                Html::tag('td', $this->renderTrailActiveRecordAttribute($newValue), ['class' => 'new']),
            ];

            $content = Html::tag('tr', implode('', $cells));
            $content = Html::tag('tbody', $content, ['class' => 'change change-rep']);

            return Html::tag('table', $content, ['class' => 'diff-wrapper diff diff-html diff-side-by-side']);
        }

        return DiffHelper::calculate((string)$oldValue, (string)$newValue, 'SideBySide', [], [
            'showHeader' => false,
            'lineNumbers' => false,
        ]);
    }

    protected function renderTrailActiveRecordAttribute(?ActiveRecord $model): string
    {
        if (!$model) {
            return '';
        }

        /** @var ActiveRecord|TrailBehavior $model */
        $name = $model->getBehavior('TrailBehavior')
            ? $model->getTrailModelName()
            : $model->getPrimaryKey();

        return Html::a($name, Trail::getAdminRouteByModel($model), ['class' => 'strong']);
    }

    protected function renderTrailAttributes(array $rows, array $options = []): string
    {
        Html::addCssClass($options, 'trail-table');
        return $rows ? Html::tag('table', Html::tableBody($rows), $options) : '';
    }

    protected function renderDataModelContent(Trail $trail): string
    {
        return $this->renderI18nTrailMessage($trail, $trail->getDataModelClass());
    }

    protected function renderMessageContent(Trail $trail): string
    {
        if ($trail->message) {
            return trim(($this->getTranslations()[$trail->message] ?? $trail->message) . ' ' . $this->renderDataTrailLink($trail));
        }

        return $this->renderI18nTrailMessage($trail, $trail->getModelClass());
    }

    protected function renderI18nTrailMessage(Trail $trail, ?Model $model = null): string
    {
        if ($model) {
            /** @var TrailBehavior $model */
            $name = $model->getTrailModelName();
            $model = ($route = $model->getTrailModelAdminRoute()) ? Html::a($name, $route) : $name;
        } else {
            $model = Html::tag('em', Yii::t('skeleton', 'Deleted'));
        }

        $options = $trail->getTypeOptions();
        $message = '';

        if (isset($options['message'])) {
            $message .= Yii::t($options['messageCategory'] ?? 'skeleton', $options['message'], [
                'model' => $model,
            ]);
        }

        return trim($message . ' ' . $this->renderDataTrailLink($trail));
    }

    public function userColumn(): array
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

    public function createdAtColumn(): array
    {
        return [
            'class' => TimeagoColumn::class,
            'attribute' => 'created_at',
            'contentOptions' => ['class' => 'text-nowrap'],
            'displayAtBreakpoint' => 'md',
        ];
    }

    protected function renderDataTrailLink(Trail $trail): string
    {
        if ($trailId = ($trail->data['trail_id'] ?? false)) {
            $link = isset($this->dataProvider->getModels()[$trailId]) ? Url::current(['#' => 'trail-' . $trailId]) : ['index', 'id' => $trailId];
            return Html::a('(#' . $trailId . ')', $link);
        }

        return '';
    }

    /**
     * Wraps behavior method and makes sure value is cast to string to prevent {@see \Jfcherng\Diff\Differ} to throw
     * errors.
     */
    protected function formatTrailAttributeValue(Model $model, string $attribute, mixed $value): mixed
    {
        /** @var TrailBehavior $model */
        return $model->formatTrailAttributeValue($attribute, $value);
    }

    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['index', 'id' => $model->id];
    }

    protected function getTrailModelRoute(Trail $trail): ?array
    {
        return ['index', 'model' => implode('@', array_filter([$trail->model, (string)$trail->model_id]))];
    }
}
