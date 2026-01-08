<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Forms\Footers;

use DateTimeInterface;
use Hirtz\Skeleton\Base\Traits\ContainerConfigurationTrait;
use Hirtz\Skeleton\Html\A;
use Hirtz\Skeleton\Html\Custom\RelativeTime;
use Hirtz\Skeleton\Html\Li;
use Hirtz\Skeleton\Models\Interfaces\TrailModelInterface;
use Hirtz\Skeleton\Models\Queries\UserQuery;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Widgets\Traits\ModelWidgetTrait;
use Hirtz\Skeleton\Widgets\Username;
use Stringable;
use Yii;
use yii\db\ActiveRecord;

class UpdatedAtFooterItem implements Stringable
{
    use ContainerConfigurationTrait;
    use ModelWidgetTrait;

    protected string $attributeName = 'updated_at';
    protected DateTimeInterface|int|string|null $value = null;

    public function value(DateTimeInterface|int|string|null $value): static
    {
        $this->value = $value;
        return $this;
    }

    protected function getItem(): ?Li
    {
        $this->value ??= in_array($this->attributeName, $this->model->attributes(), true)
            ? $this->model->{$this->attributeName}
            : null;

        if (!$this->value) {
            return null;
        }

        $updated = $this->getUpdated();
        $timestamp = RelativeTime::make()->value($this->value);

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
