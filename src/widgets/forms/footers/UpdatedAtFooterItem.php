<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\widgets\forms\footers;

use Hirtz\Skeleton\base\traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\html\A;
use Hirtz\Skeleton\html\custom\RelativeTime;
use Hirtz\Skeleton\html\Li;
use Hirtz\Skeleton\models\interfaces\TrailModelInterface;
use Hirtz\Skeleton\models\queries\UserQuery;
use Hirtz\Skeleton\models\Trail;
use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\widgets\traits\ModelWidgetTrait;
use Hirtz\Skeleton\widgets\Username;
use Stringable;
use Yii;
use yii\db\ActiveRecord;

class UpdatedAtFooterItem implements Stringable
{
    use ContainerConfigurationTrait;
    use ModelWidgetTrait;

    public string $attributeName = 'updated_at';

    protected function getItem(): ?Li
    {
        $updatedAt = in_array($this->attributeName, $this->model->attributes(), true)
            ? $this->model->{$this->attributeName}
            : null;

        if (!$updatedAt) {
            return null;
        }

        $updated = $this->getUpdated();
        $timestamp = RelativeTime::make()->value($this->model->{$this->attributeName});

        $content = $updated
            ? Yii::t('skeleton', 'Last updated by {user} {timestamp}', [
                'user' => Username::make()->user($updated),
                'timestamp' => $timestamp,
            ])
            : Yii::t('skeleton', 'Last updated {timestamp}', [
                'timestamp' => $timestamp,
            ]);

        $url = $this->model instanceof TrailModelInterface && Yii::$app->getUser()->can(Trail::AUTH_TRAIL_INDEX)
            ? Trail::getAdminRouteByModel($this->model)
            : null;

        if ($url) {
            $content = A::make()
                ->content($content)
                ->href($url);
        }

        return Li::make()
            ->class('form-footer-item')
            ->content($content);
    }

    protected function getUpdated(): ?User
    {
        if (!$this->model instanceof ActiveRecord) {
            return null;
        }

        $user = $this->model->getRelatedRecords()['updated'] ?? null;

        if ($user instanceof User) {
            return $user;
        }

        /** @var UserQuery<User>|null $relation */
        $relation = $this->model->getRelation('updated', false);
        return $relation?->one();
    }

    public function __toString(): string
    {
        return (string)$this->getItem();
    }
}
