<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Behaviors;

use Hirtz\Skeleton\Db\ActiveRecord;
use Override;

/**
 * @property ActiveRecord $owner
 */
class BlameableBehavior extends \yii\behaviors\BlameableBehavior
{
    public $createdByAttribute = 'created_by_user_id';
    public $updatedByAttribute = 'updated_by_user_id';

    #[Override]
    public function init(): void
    {
        $this->attributes = $this->attributes ?: [
            ActiveRecord::EVENT_BEFORE_INSERT => [$this->updatedByAttribute],
            ActiveRecord::EVENT_BEFORE_UPDATE => [$this->updatedByAttribute],
        ];

        parent::init();
    }
}
