<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use Hirtz\Skeleton\Widgets\Grids\GridView;
use yii\base\Model;

class GridSummary extends Widget
{
    use GridTrait;

    protected ?string $message = null;
    protected ?string $emptyMessage = null;
    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    public function message(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * What the grid is for, said only where the grid is empty and nothing was searched for — a filled grid
     * explains itself, and a fruitless search needs the search summary rather than the explanation.
     */
    public function emptyMessage(?string $emptyMessage): static
    {
        $this->emptyMessage = $emptyMessage;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return $this->getAlert();
    }

    protected function getAlert(): Alert
    {
        $alert = Alert::make()
            ->content($this->getAlertContent());

        if ($this->grid->provider->getTotalCount()) {
            $alert->info();
        } else {
            $alert->warning();
        }

        if ($this->grid->search->getValue()) {
            $alert->button(Button::make()
                ->class('btn-icon icon')
                ->get($this->grid->search->getUrl())
                ->tooltip(Yii::t('skeleton', 'GRID_SUMMARY_CLEAR_SEARCH'))
                ->icon('xmark'));
        }

        return $alert;
    }

    protected function getAlertContent(): string
    {
        $pagination = $this->grid->provider->getPagination();
        $count = $this->grid->provider->getCount();
        $totalCount = $this->grid->provider->getTotalCount();

        $params = [
            'search' => $this->grid->search->getValue(),
            'totalCount' => $this->grid->provider->getTotalCount(),
        ];

        if ($pagination !== false) {
            $begin = $pagination->getPage() * $pagination->getPageSize() + 1;

            $params['page'] = $pagination->getPage() + 1;
            $params['pageCount'] = $pagination->getPageCount();
            $params['end'] = $begin + $count - 1;
            $params['begin'] = min($begin, $params['end']);
        }

        $params = [...$params, ...$this->params];

        if ($this->message) {
            return Yii::$app->getI18n()->format($this->message, $params, Yii::$app->language);
        }

        if ($this->emptyMessage && !$count && !$this->grid->search->getValue()) {
            return Yii::$app->getI18n()->format($this->emptyMessage, $params, Yii::$app->language);
        }

        if ($this->grid->search->getValue()) {
            return match ($count) {
                1 => Yii::t('skeleton', 'GRID_SUMMARY_DISPLAYING_ONLY', $params),
                0 => Yii::t('skeleton', 'GRID_SUMMARY_NO_RESULTS_SEARCH', $params),
                $totalCount => Yii::t('skeleton', 'GRID_SUMMARY_DISPLAYING_ALL_RESULTS_MATCHING', $params),
                default => Yii::t('skeleton', 'GRID_SUMMARY_DISPLAYING_OF_RESULTS_MATCHING', $params),
            };
        }

        return match ($count) {
            1 => Yii::t('skeleton', 'GRID_SUMMARY_DISPLAYING_THE_ONLY_RECORD', $params),
            0 => Yii::t('skeleton', 'GRID_SUMMARY_NO_RESULTS', $params),
            $totalCount => Yii::t('skeleton', 'GRID_SUMMARY_DISPLAYING_ALL_RECORDS', $params),
            default => Yii::t('skeleton', 'GRID_SUMMARY_DISPLAYING_OF_RECORDS', $params),
        };
    }
}
