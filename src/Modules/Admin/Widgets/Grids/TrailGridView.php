<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Grids;

use Hirtz\Skeleton\Db\ActiveRecord;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Html\Table;
use Hirtz\Skeleton\Html\Td;
use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Models\Collections\TrailModelCollection;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Widgets\Grids\Columns\DataColumn;
use Hirtz\Skeleton\Widgets\Grids\Columns\RelativeTimeColumn;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use Hirtz\Skeleton\Widgets\Grids\Traits\MessageSourceTrait;
use Hirtz\Skeleton\Widgets\Grids\Traits\TypeGridViewTrait;
use Hirtz\Skeleton\Widgets\Username;
use Jfcherng\Diff\DiffHelper;
use Override;
use Stringable;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecordInterface;

/**
 * @extends GridView<Trail>
 * @property TrailActiveDataProvider $provider
 */
class TrailGridView extends GridView
{
    use MessageSourceTrait;
    use TypeGridViewTrait;

    public array $tableAttributes = [
        'class' => 'trail-table table table-striped',
    ];

    #[Override]
    public function configure(): void
    {
        $this->model ??= Trail::instance();

        $this->rowAttributes = fn (Trail $trail) => [
            'class' => $trail->isDeleteType() ? 'trail-delete' : '',
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
            ->headerAttributes(['class' => 'trail-model-col'])
            ->content($this->getModelColumnContent(...))
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

    protected function getDataColumnContent(Trail $trail): string|Stringable
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

    protected function getCreateAttributesContent(Trail $trail): string|Stringable
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
                    $rows[] = [
                        Td::make()
                            ->class('trail-property-col')
                            ->text($attribute),
                        Td::make()
                            ->class('trail-value-col')
                            ->content($this->getCreatedAttributeContent($value)),
                    ];
                }
            }
        }

        return $rows
            ? $this->getTrailAttributesTable($rows)
                ->addClass('trail-insert')
            : '';
    }

    protected function getCreatedAttributeContent(mixed $value): string|Stringable|null
    {
        if ($value instanceof ActiveRecord) {
            return $this->getTrailActiveRecordAttribute($value);
        }

        return is_array($value)
            ? Ul::make()->items(...array_map(strval(...), $value))
            : Html::encode($value);
    }

    protected function getUpdateAttributesContent(Trail $trail): string|Stringable
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
                    $rows[] = [
                        Td::make()
                            ->class('trail-property-col')
                            ->text($attribute),
                        Td::make()
                            ->class('trail-value-col')
                            ->content($this->getUpdatedAttributeContent($values[0], $values[1])),
                    ];
                }
            }
        }

        return $rows
            ? $this->getTrailAttributesTable($rows)->addClass('trail-update')
            : '';
    }

    protected function getUpdatedAttributeContent(mixed $oldValue, mixed $newValue): string|Stringable
    {
        if ($oldValue instanceof ActiveRecord || $newValue instanceof ActiveRecord) {
            return Table::make()
                ->class('trail-diff-table table')
                ->rows([
                    [
                        Td::make()
                            ->class('old')
                            ->content($this->getTrailActiveRecordAttribute($oldValue)),
                        Td::make()
                            ->class('new')
                            ->content($this->getTrailActiveRecordAttribute($newValue)),
                    ],
                ]);
        }

        if (is_array($oldValue)) {
            $oldValue = implode("\n", $oldValue);
        }

        if (is_array($newValue)) {
            $newValue = implode("\n", $newValue);
        }

        return DiffHelper::calculate((string)$oldValue, (string)$newValue, 'SideBySide', [], [
            'wrapperClasses' => ['trail-diff-table table'],
            'showHeader' => false,
            'lineNumbers' => false,
        ]);
    }

    protected function getTrailActiveRecordAttribute(?ActiveRecord $model): ?Stringable
    {
        if (!$model?->getPrimaryKey()) {
            return null;
        }

        $name = $model instanceof TrailModelInterface ? $model->getTrailModelName() : $model->getPrimaryKey();

        return A::make()
            ->text($name)
            ->href(Trail::getAdminRouteByModel($model))
            ->class('strong');
    }

    protected function getTrailAttributesTable(array $rows): Table
    {
        return Table::make()
            ->class('table')
            ->rows($rows);
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
            $route = $model->getTrailModelAdminRoute();

            if ($route) {
                $name = A::make()
                    ->text($name)
                    ->href($route)
                    ->render();
            }
        }

        $name ??= Div::make()
            ->text(Yii::t('skeleton', 'Deleted'))
            ->class('text-invalid');

        $options = $trail->getTypeOptions();
        $message = '';

        if ($options['message'] ?? false) {
            $message .= Yii::t($options['messageCategory'] ?? 'skeleton', $options['message'], [
                'model' => $name,
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

    protected function getCreatedAtColumn(): RelativeTimeColumn
    {
        return RelativeTimeColumn::make()
            ->property('created_at')
            ->hiddenForSmallDevices();
    }

    #[Override]
    protected function getRoute(ActiveRecordInterface $model, array $params = []): array|false
    {
        return ['index', 'id' => $model->getPrimaryKey()];
    }

    protected function getTrailModelRoute(Trail $trail): ?array
    {
        return ['index', 'model' => implode('@', array_filter([$trail->model, (string)$trail->model_id]))];
    }
}
