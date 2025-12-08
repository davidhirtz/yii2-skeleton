<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms\fields;

use DateTime;
use DateTimeZone;
use Hirtz\Skeleton\html\Input;
use Hirtz\Skeleton\html\traits\TagInputTrait;
use Hirtz\Skeleton\widgets\forms\InputGroup;
use Stringable;
use Yii;

class DateTimeField extends Field
{
    use TagInputTrait;

    private string|DateTimeZone $timeZone;

    public function timeZone(string|DateTimeZone $timeZone): static
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->timeZone ??= Yii::$app->getTimeZone() ?: 'UTC';

        if (is_string($this->timeZone)) {
            $this->timeZone = new DateTimeZone($this->timeZone);
        }

        $this->attributes['type'] ??= 'datetime-local';
        $this->attributes['value'] ??= $this->model?->{$this->property};

        if ($this->attributes['value'] instanceof DateTime) {
            $value = $this->attributes['value']->setTimezone($this->timeZone);
            $this->attributes['value'] = $value->format('Y-m-d\TH:i');
        }

        parent::configure();
    }

    protected function getInput(): string|Stringable
    {
        $input = Input::make()
            ->attributes($this->attributes)
            ->addClass('input');

        return InputGroup::make()
            ->append($this->getTimeZone())
            ->content($input);
    }

    protected function getTimeZone(): ?string
    {
        $offset = $this->timeZone->getOffset(new DateTime());
        $abs = abs($offset);
        $hours = intdiv($abs, 3600);
        $minutes = intdiv($abs % 3600, 60);

        return 'GMT' . ($offset >= 0 ? '+' : '-') . sprintf('%02d:%02d', $hours, $minutes);
    }
}
