<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\grids;

use davidhirtz\yii2\skeleton\html\Alert;
use davidhirtz\yii2\skeleton\html\Button;
use davidhirtz\yii2\skeleton\widgets\grids\toolbars\GridSearch;
use Stringable;
use Yii;
use yii\data\Pagination;

class GridSummary implements Stringable
{
    public function __construct(
        protected int $count,
        protected int $totalCount,
        protected Pagination|false $pagination = false,
        protected ?GridSearch $search = null,
        protected ?string $message = null,
        protected array $params = [],
    ) {
    }

    public function render(): Stringable
    {
        return $this->getAlert();
    }

    protected function getAlert(): Alert
    {
        $alert = Alert::make()
            ->content($this->getContent());

        if ($this->totalCount) {
            $alert->info();
        } else {
            $alert->warning();
        }

        if ($this->search?->getValue()) {
            $alert->button(Button::make()
                ->class('btn-icon')
                ->get($this->search->getUrl())
                ->tooltip(Yii::t('skeleton', 'Clear Search'))
                ->icon('xmark'));
        }

        return $alert;
    }

    protected function getContent(): string
    {
        $params = [
            'search' => $this->search?->getValue(),
            'totalCount' => $this->totalCount,
        ];

        if ($this->pagination !== false) {
            $begin = $this->pagination->getPage() * $this->pagination->getPageSize() + 1;

            $params['page'] = $this->pagination->getPage() + 1;
            $params['pageCount'] = $this->pagination->getPageCount();
            $params['end'] = $begin + $this->count - 1;
            $params['begin'] = min($begin, $params['end']);
        }

        $params = [...$params, ...$this->params];

        if ($this->message) {
            return Yii::$app->getI18n()->format($this->message, $params, Yii::$app->language);
        }

        if ($this->search?->getValue()) {
            return match ($this->count) {
                1 => Yii::t('skeleton', 'Displaying the only result matching "{search}".', $params),
                0 => Yii::t('skeleton', 'Sorry, no results found matching matching "{search}".', $params),
                $this->totalCount => Yii::t('skeleton', 'Displaying all {totalCount, number} results matching "{search}".', $params),
                default => Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} results matching "{search}".', $params),
            };
        }

        return match ($this->count) {
            1 => Yii::t('skeleton', 'Displaying the only record.', $params),
            0 => Yii::t('skeleton', 'Sorry, no records found.', $params),
            $this->totalCount => Yii::t('skeleton', 'Displaying all {totalCount, number} records.', $params),
            default => Yii::t('skeleton', 'Displaying {begin, number}-{end, number} of {totalCount, number} records.', $params),
        };
    }

    public function __toString(): string
    {
        return (string)$this->render();
    }
}
