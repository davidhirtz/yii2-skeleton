<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Hirtz\Skeleton\I18n\Lang;
use Hirtz\Skeleton\Widgets\Alert;
use Hirtz\Skeleton\Widgets\Buttons\Button;
use Hirtz\Skeleton\Widgets\Grids\Traits\GridTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

class GridSummary extends Widget
{
    use GridTrait;

    protected ?string $message = null;
    protected array $params = [];

    public function message(string $message): static
    {
        $this->message = $message;
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

        if ($this->grid->search?->getValue()) {
            $alert->button(Button::make()
                ->class('btn-icon icon')
                ->get($this->grid->search->getUrl())
                ->tooltip(Lang::t('skeleton', 'GRID_SUMMARY_CLEAR_SEARCH'))
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
            'search' => $this->grid->search?->getValue(),
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

        if ($this->grid->search?->getValue()) {
            return match ($count) {
                1 => Lang::t('skeleton', 'GRID_SUMMARY_DISPLAYING_THE_ONLY_RESULT_MATCHING', $params),
                0 => Lang::t('skeleton', 'GRID_SUMMARY_SORRY_NO_RESULTS_FOUND_MATCHING_MATCHING', $params),
                $totalCount => Lang::t('skeleton', 'GRID_SUMMARY_DISPLAYING_ALL_RESULTS_MATCHING', $params),
                default => Lang::t('skeleton', 'GRID_SUMMARY_DISPLAYING_OF_RESULTS_MATCHING', $params),
            };
        }

        return match ($count) {
            1 => Lang::t('skeleton', 'GRID_SUMMARY_DISPLAYING_THE_ONLY_RECORD', $params),
            0 => Lang::t('skeleton', 'GRID_SUMMARY_SORRY_NO_RECORDS_FOUND', $params),
            $totalCount => Lang::t('skeleton', 'GRID_SUMMARY_DISPLAYING_ALL_RECORDS', $params),
            default => Lang::t('skeleton', 'GRID_SUMMARY_DISPLAYING_OF_RECORDS', $params),
        };
    }
}
