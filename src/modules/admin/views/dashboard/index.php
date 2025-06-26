<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\DashboardController::actionIndex()
 * @var View $this
 * @var array $panels
 */

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\ListGroup;
use davidhirtz\yii2\skeleton\html\ListGroupItemLink;
use davidhirtz\yii2\skeleton\web\View;

$this->setTitle(Yii::t('skeleton', 'Admin'));
?>
<h1 class="page-header"><?= Yii::$app->name; ?></h1>

<div class="row flex-wrap justify-center">
    <?php foreach ($panels as $panel) {
        ?>
        <div class="dashboard-item">
            <?php
            $list = ListGroup::make();

        foreach ($panel['items'] as $item) {
            $list->addLink(ListGroupItemLink::make()
                ->text($item['label'])
                ->href($item['url'])
                ->icon($item['icon'] ?? null));
        }

        echo Card::make()
            ->addClass('dashboard-card')
            ->title($panel['name'])
            ->html($list)
            ->render();
        ?>
        </div>
        <?php
    }
?>
</div>
