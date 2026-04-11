<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Panels;

use Hirtz\Skeleton\Html\Ul;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Widgets\Navs\Traits\ItemTrait;
use Hirtz\Skeleton\Widgets\Traits\ContainerTrait;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * @property Module $module
 */
class Dashboard extends Widget
{
    use ContainerTrait;

    /** @use ItemTrait<DashboardItem> */
    use ItemTrait;

    protected Module $module;

    public function __construct(array $config = [])
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $this->module = $module;

        $this->module->dashboard($this);

        parent::__construct($config);
    }

    #[Override]
    protected function renderContent(): Stringable
    {
        return Ul::make()
            ->class('dashboard')
            ->content(...$this->items);
    }
}
