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
                ->tooltip(Yii::t('skeleton', 'Clear Search'))
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
                1 => Yii::t('skeleton', 'Displaying the only result matching "{search}".', $params),
                0 => Yii::t('skeleton', 'Sorry, no results found matching matching "{search}".', $params),
                $totalCount => Yii::t('skeleton', 'Displaying all {totalCount, number} results matching "{search}".', $params),
                default => Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} results matching "{search}".', $params),
            };
        }

        return match ($count) {
            1 => Yii::t('skeleton', 'Displaying the only record.', $params),
            0 => Yii::t('skeleton', 'Sorry, no records found.', $params),
            $totalCount => Yii::t('skeleton', 'Displaying all {totalCount, number} records.', $params),
            default => Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} records.', $params),
        };
    }
}
