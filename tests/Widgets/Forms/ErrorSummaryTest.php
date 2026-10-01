<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Widgets\Forms;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Widgets\Forms\ErrorSummary;
use yii\base\DynamicModel;

class ErrorSummaryTest extends TestCase
{
    public function testASingleErrorIsEscaped(): void
    {
        $model = new DynamicModel(['name']);
        $model->addError('name', 'Name "<b hx-get=/x>p</b>" has already been taken.');

        $html = (string)ErrorSummary::make()->models($model);

        self::assertStringNotContainsString('<b hx-get', $html);
        self::assertStringContainsString('&lt;b hx-get=/x&gt;p&lt;/b&gt;', $html);
    }

    public function testEveryErrorOfAListIsEscaped(): void
    {
        $model = new DynamicModel(['name', 'url']);
        $model->addError('name', '<b hx-get=/x>name</b>');
        $model->addError('url', '<i>url</i> & more');

        $html = (string)ErrorSummary::make()->models($model);

        self::assertStringContainsString('<li>&lt;b hx-get=/x&gt;name&lt;/b&gt;</li>', $html);
        self::assertStringContainsString('<li>&lt;i&gt;url&lt;/i&gt; &amp; more</li>', $html);
    }
}
