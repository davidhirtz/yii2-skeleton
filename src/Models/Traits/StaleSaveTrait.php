<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Yii;

/**
 * Two editors on one record: the second save overwrote the first without a word. The form posts back when it was
 * rendered ({@see \Hirtz\Skeleton\Widgets\Forms\ActiveForm}), and a save is refused while the record has an update
 * in its trail from after that — the trail rather than `updated_at`, which a count updater bumps without anybody
 * editing. Opting in means spreading {@see static::getStaleSaveRules()} into `rules()`.
 *
 * @phpstan-require-implements \Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface
 */
trait StaleSaveTrait
{
    /**
     * @var int|null the time the form was rendered, posted back with it; `null` checks nothing
     */
    public ?int $loadedAt = null;

    public function getLoadedAt(): ?int
    {
        return $this->loadedAt;
    }

    /**
     * @return list<array<int|string, mixed>>
     */
    protected function getStaleSaveRules(): array
    {
        return [
            [
                ['loadedAt'],
                'integer',
            ],
            [
                ['loadedAt'],
                $this->validateStaleSave(...),
            ],
        ];
    }

    public function validateStaleSave(): void
    {
        $trail = $this->findUpdateTrailSince();

        if ($trail === null) {
            return;
        }

        $user = $trail->user;

        $this->addError('loadedAt', Yii::t('skeleton', 'COMMON_STALE_SAVE_ERROR', [
            'user' => $user instanceof User ? $user->getUsername() : '',
            'time' => Yii::$app->getFormatter()->asDatetime($trail->created_at, 'short'),
        ]));
    }

    /**
     * The newest update another save wrote after the form was rendered. Seconds are what the trail keeps, so two
     * saves within the same one are not told apart.
     */
    public function findUpdateTrailSince(): ?Trail
    {
        if (!$this->loadedAt || $this->getIsNewRecord()) {
            return null;
        }

        return Trail::find()
            ->where([
                'model_class' => $this->getTrailBehavior()->modelClass,
                'model_id' => implode('-', array_map(strval(...), $this->getPrimaryKey(true))),
                'type' => Trail::TYPE_UPDATE,
            ])
            ->andWhere(['>', 'created_at', gmdate('Y-m-d H:i:s', $this->loadedAt)])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();
    }
}
