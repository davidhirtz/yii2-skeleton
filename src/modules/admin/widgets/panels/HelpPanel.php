<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\modules\admin\widgets\panels;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use Yii;

class HelpPanel extends Panel
{
    public array $wrapOptions = [
        'class' => 'row',
    ];

    public array $contentOptions = [
        'class' => 'col-form-content',
    ];

    public function init(): void
    {
        $this->title ??= Yii::t('skeleton', 'Operations');

        if ($this->content) {
            $this->content = Html::tag('div', Html::tag('div', $this->content, $this->contentOptions), $this->wrapOptions);
        }

        parent::init();
    }

    public function renderHelpBlock(string $text): string
    {
        return Html::tag('p', $text);
    }

    public function renderButtonToolbar(array|string $buttons): string
    {
        return $buttons ? Html::tag('div', Html::buttons($buttons), ['class' => 'card-buttons']) : '';
    }
}
