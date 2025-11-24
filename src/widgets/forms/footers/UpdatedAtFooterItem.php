<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\widgets\forms\footers;

use davidhirtz\yii2\skeleton\base\traits\ContainerConfigurationTrait;
use davidhirtz\yii2\skeleton\html\A;
use davidhirtz\yii2\skeleton\html\Li;
use davidhirtz\yii2\skeleton\models\interfaces\TrailModelInterface;
use davidhirtz\yii2\skeleton\models\queries\UserQuery;
use davidhirtz\yii2\skeleton\models\Trail;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\widgets\traits\ModelWidgetTrait;
use davidhirtz\yii2\skeleton\widgets\Username;
use davidhirtz\yii2\timeago\Timeago;
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
        $timestamp = Timeago::tag($this->model->{$this->attributeName});

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
