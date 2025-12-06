<?php
declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\fields;

use DateTime;
use DateTimeZone;
use davidhirtz\yii2\datetime\Date;
use davidhirtz\yii2\skeleton\html\Input;
use davidhirtz\yii2\skeleton\html\traits\TagInputTrait;
use davidhirtz\yii2\skeleton\widgets\forms\InputGroup;
use davidhirtz\yii2\skeleton\widgets\forms\traits\InputGroupTrait;
use Exception;
use Stringable;
use Yii;

class DateTimeField extends Field
{
    use TagInputTrait;

    protected function configure(): void
    {
        $this->attributes['type'] ??= 'datetime-local';
        $this->attributes['value'] ??= $this->model?->{$this->property};

        if ($this->attributes['value'] instanceof DateTime) {
            $this->attributes['value'] = $this->attributes['value']->format('Y-m-d\TH:i');
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
        $tz = Yii::$app->getTimeZone();

        if (!$tz) {
            return null;
        }

        $offset = (new DateTimeZone($tz))->getOffset(new DateTime());
        $sign = $offset >= 0 ? '+' : '-';
        $abs = abs($offset);
        $hours = intdiv($abs, 3600);
        $minutes = intdiv($abs % 3600, 60);

        return "GMT$sign" . sprintf('%02d:%02d', $hours, $minutes);
    }
}