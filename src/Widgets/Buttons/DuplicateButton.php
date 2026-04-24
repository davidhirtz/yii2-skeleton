<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Buttons;

use Hirtz\Skeleton\Helpers\Url;
use Hirtz\Skeleton\Html\Traits\TagAttributesTrait;
use Hirtz\Skeleton\Widgets\Traits\IconTrait;
use Hirtz\Skeleton\Widgets\Traits\LabelTrait;
use Hirtz\Skeleton\Widgets\Traits\ModelTrait;
use Hirtz\Skeleton\Widgets\Traits\UrlTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;
use yii\db\ActiveRecordInterface;

class DuplicateButton extends Widget
{
    use IconTrait;
    use LabelTrait;

    /**
     * @use ModelTrait<ActiveRecordInterface>
     */
    use ModelTrait;

    use TagAttributesTrait;
    use UrlTrait;


    #[Override]
    protected function configure(): void
    {
        $this->icon ??= 'copy';
        $this->label ??= Yii::t('skeleton', 'Duplicate');
        $this->url ??= Url::toRoute(['duplicate', 'id' => $this->model->getPrimaryKey()]);

        parent::configure();
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        return Button::make()
                ->attributes($this->attributes)
                ->primary()
                ->post($this->url, true)
                ->text($this->label)
                ->icon($this->icon);
    }
}
