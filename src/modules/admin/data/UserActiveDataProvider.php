<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\modules\admin\data;

use Hirtz\Skeleton\data\ActiveDataProvider;
use Hirtz\Skeleton\models\queries\UserQuery;
use Hirtz\Skeleton\models\User;
use Override;

/**
 * @property UserQuery $query
 * @extends ActiveDataProvider<User>
 */
class UserActiveDataProvider extends ActiveDataProvider
{
    public ?string $searchString = null;
    public ?int $status = null;

    public function __construct($config = [])
    {
        $this->query = User::find();
        parent::__construct($config);
    }

    #[Override]
    protected function prepareQuery(): void
    {
        $this->initQuery();
        parent::prepareQuery();
    }

    protected function initQuery(): void
    {
        if ($this->searchString) {
            $this->query->matching($this->searchString);
        }

        if ($this->status !== null) {
            $this->query->andWhere(['status' => $this->status]);
        }
    }

    #[Override]
    public function setSort($value): void
    {
        if (is_array($value)) {
            $value['defaultOrder'] ??= ['last_login' => SORT_DESC];
        }

        parent::setSort($value);
    }

    #[Override]
    public function setPagination($value): void
    {
        if (is_array($value)) {
            $value['defaultPageSize'] ??= 50;
        }

        parent::setPagination($value);
    }
}
