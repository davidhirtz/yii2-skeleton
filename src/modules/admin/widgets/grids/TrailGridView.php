<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\grids;

use davidhirtz\yii2\skeleton\db\ActiveRecord;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Div;
use davidhirtz\yii2\skeleton\models\collections\TrailModelCollection;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\widgets\grids\columns\DataColumn;
use davidhirtz\yii2\skeleton\widgets\grids\columns\TimeagoColumn;
use davidhirtz\yii2\skeleton\widgets\grids\GridView;
use davidhirtz\yii2\skeleton\widgets\grids\traits\MessageSourceTrait;
use davidhirtz\yii2\skeleton\widgets\grids\traits\TypeGridViewTrait;
use davidhirtz\yii2\skeleton\widgets\Username;
use Jfcherng\Diff\DiffHelper;
use Override;
use Stringable;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;
use yii\helpers\Url;

/**
 * @extends GridView<Trail>
 * @property TrailActiveDataProvider $provider
 */
class TrailGridView extends GridView
{
    use MessageSourceTrait;
    use TypeGridViewTrait;

    public array $tableAttributes = [
        'class' => 'table table-striped trail',
    ];

    #[Override]
    public function configure(): void
    {
        $this->rowAttributes = fn (Trail $trail) => [
            'class' => $trail->isDeleteType() ? 'bg-danger' : '',
        ];

        $this->columns ??= [
            $this->getTypeIconColumn(),
            $this->getModelColumn(),
            $this->getDataColumn(),
            $this->getUserColumn(),
            $this->getCreatedAtColumn(),
        ];

        $this->messageSourceAttribute = 'message';

        parent::configure();
    }

    protected function getModelColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('model')
            ->content($this->getModelColumnContent(...))
            ->contentAttributes(['style' => 'width:300px'])
            ->visible(!$this->provider->model);
    }

    protected function getModelColumnContent(Trail $trail): array|string
    {
        if ($trail->model) {
            $model = $trail->getModelClass();
            $isModel = $model instanceof ActiveRecord && !$model->getIsNewRecord();

            $content = [
                A::make()
                    ->content($trail->getModelName())
                    ->href($this->getTrailModelRoute($trail))
                    ->class($isModel ? 'strong' : 'italic'),
            ];

            $type = $isModel ? $trail->getModelType() : false;

            if ($type) {
                $content[] = Div::make()
                    ->content($type)
                    ->class('small');
            }

            return $content;
        }

        return '';
    }

    protected function getDataColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('data')
            ->content($this->getDataColumnContent(...));
    }

    protected function getDataColumnContent(Trail $trail): string
    {
        if ($trail->isAuthPermissionType()) {
            return $this->getAuthPermissionContent($trail);
        }

        if ($trail->hasAttributesEnabled()) {
            if ($trail->isCreateType()) {
                return $this->getCreateAttributesContent($trail);
            }

            if ($trail->isUpdateType()) {
                return $this->getUpdateAttributesContent($trail);
            }
        }

        if ($trail->hasDataModelEnabled()) {
            return $this->getDataModelContent($trail);
        }

        return $this->getMessageContent($trail);
    }

    protected function getAuthPermissionContent(Trail $trail): string
    {
        $params = [
            'permission' => Html::tag($trail->isAuthPermissionAssignType() ? 'ins' : 'del', $this->getTranslations()[$trail->message] ?? $trail->message),
        ];

        return $trail->isAuthPermissionAssignType() ? Yii::t('skeleton', 'Permission {permission} assigned', $params) :
            Yii::t('skeleton', 'Permission {permission} revoked', $params);
    }

    protected function getCreateAttributesContent(Trail $trail): string
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
                    $rows[] = [$attribute, $this->getCreatedAttributeValue($value)];
                }
            }
        }

        return $this->getTrailAttributes($rows, ['class' => 'trail-insert']);
    }

    protected function getCreatedAttributeValue(mixed $value): string
    {
        if ($value instanceof ActiveRecord) {
            return $this->getTrailActiveRecordAttribute($value);
        }

        return is_array($value) ? Html::ul($value) : Html::encode($value);
    }

    protected function getUpdateAttributesContent(Trail $trail): string
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
                    $rows[] = [$attribute, $this->getUpdatedAttributeValues($values[0], $values[1])];
                }
            }
        }

        return $this->getTrailAttributes($rows, ['class' => 'trail-update']);
    }

    protected function getUpdatedAttributeValues(mixed $oldValue, mixed $newValue): string
    {
        if ($oldValue instanceof ActiveRecord || $newValue instanceof ActiveRecord) {
            $cells = [
                Html::tag('td', $this->getTrailActiveRecordAttribute($oldValue), ['class' => 'old']),
                Html::tag('td', $this->getTrailActiveRecordAttribute($newValue), ['class' => 'new']),
            ];

            $content = Html::tag('tr', implode('', $cells));
            $content = Html::tag('tbody', $content, ['class' => 'change change-rep']);

            return Html::tag('table', $content, ['class' => 'diff-wrapper diff diff-html diff-side-by-side']);
        }

        if (is_array($oldValue)) {
            $oldValue = implode("\n", $oldValue);
        }

        if (is_array($newValue)) {
            $newValue = implode("\n", $newValue);
        }

        return DiffHelper::calculate((string)$oldValue, (string)$newValue, 'SideBySide', [], [
            'showHeader' => false,
            'lineNumbers' => false,
        ]);
    }

    protected function getTrailActiveRecordAttribute(?ActiveRecord $model): string
    {
        if (!$model?->getPrimaryKey()) {
            return '';
        }

        $name = $model instanceof TrailModelInterface ? $model->getTrailModelName() : $model->getPrimaryKey();

        return Html::a($name, Trail::getAdminRouteByModel($model), ['class' => 'strong']);
    }

    protected function getTrailAttributes(array $rows, array $options = []): string
    {
        Html::addCssClass($options, 'trail-table');
        return $rows ? Html::tag('table', Html::tableBody($rows), $options) : '';
    }

    protected function getDataModelContent(Trail $trail): string
    {
        return $this->renderI18nTrailMessage($trail, $trail->getDataModelClass());
    }

    protected function getMessageContent(Trail $trail): string
    {
        if ($trail->message) {
            return trim(($this->getTranslations()[$trail->message] ?? $trail->message) . ' ' . $this->renderDataTrailLink($trail));
        }

        return $this->renderI18nTrailMessage($trail, $trail->getModelClass());
    }

    protected function renderI18nTrailMessage(Trail $trail, ?Model $model = null): string
    {
        if ($model instanceof TrailModelInterface) {
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

    protected function getUserColumn(): DataColumn
    {
        return DataColumn::make()
            ->property('user_id')
            ->content($this->getUserColumnContent(...))
            ->visible(!$this->provider->user)
            ->hiddenForSmallDevices()
            ->nowrap();
    }

    protected function renderDataTrailLink(Trail $trail): string
    {
        if ($trailId = ($trail->data['trail_id'] ?? false)) {
            $link = isset($this->provider->getModels()[$trailId]) ? Url::current(['#' => 'trail-' . $trailId]) : ['index', 'id' => $trailId];
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
        return $model instanceof TrailModelInterface
            ? $model->formatTrailAttributeValue($attribute, $value)
            : TrailModelCollection::formatAttributeValue($model, $attribute, $value);
    }

    protected function getUserColumnContent(Trail $trail): string|Stringable
    {
        if (!$trail->user_id) {
            return '';
        }

        if ($trail->user) {
            return Username::make()
                ->user($trail->user)
                ->href(['index', 'user' => $trail->user_id]);
        }

        return A::make()
            ->href(['index', 'model' => User::class . ":$trail->user_id"])
            ->content(Yii::t('skeleton', '{model} #{id}', [
                'model' => Yii::t('skeleton', 'User'),
                'id' => $trail->user_id,
            ]));
    }

    protected function getCreatedAtColumn(): TimeagoColumn
    {
        return TimeagoColumn::make()
            ->property('created_at')
            ->hiddenForSmallDevices();
    }

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['index', 'id' => $model->id];
    }

    protected function getTrailModelRoute(Trail $trail): ?array
    {
        return ['index', 'model' => implode('@', array_filter([$trail->model, (string)$trail->model_id]))];
    }

    #[Override]
    public function getModel(): Trail
    {
        return Trail::instance();
    }
}
