<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Modules\Admin\Widgets\Navs;

use Hirtz\Skeleton\Widgets\Navs\Header;
use Override;
use Yii;

class SearchHeader extends Header
{
    protected string $query = '';

    public function query(string $query): static
    {
        $this->query = $query;
        return $this;
    }

    #[Override]
    protected function configure(): void
    {
        $this->title ??= $this->query !== ''
            ? Yii::t('skeleton', 'SEARCH_RESULTS_TITLE', ['query' => $this->query])
            : Yii::t('skeleton', 'SEARCH_TITLE');

        parent::configure();
    }
}
