<?php
declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\widgets\forms\fields;

use Codeception\Test\Unit;
use davidhirtz\yii2\datetime\DateTime;
use davidhirtz\yii2\skeleton\widgets\forms\fields\DateTimeField;
use Yii;

class DateTimeFieldTest extends Unit
{
    public function testDateTimeLocalField(): void
    {
        $content = DateTimeField::make()
            ->value('2024-06-15 14:30:20')
            ->render();

        $html = '<div id="i1-row" class="form-group form-row"><div class="form-content"><div class="input-group"><input type="datetime-local" id="i1" class="input" value="2024-06-15 14:30:20"><div class="input-group-append">GMT+00:00</div></div></div></div>';
        self::assertEquals($html, $content);

        $content = DateTimeField::make()
            ->timeZone('Europe/Berlin')
            ->value(new DateTime('2024-06-15T14:30:00+01:00'))
            ->render();

        $html = '<div id="i2-row" class="form-group form-row"><div class="form-content"><div class="input-group"><input type="datetime-local" id="i2" class="input" value="2024-06-15T15:30"><div class="input-group-append">GMT+01:00</div></div></div></div>';
        self::assertEquals($html, $content);
    }
}