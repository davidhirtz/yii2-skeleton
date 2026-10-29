<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Db\ActiveQuery;
use Hirtz\Skeleton\Models\Trail;
use Hirtz\Skeleton\Models\User;
use Yii;

/**
 * A save is refused while the trail holds an update from after the form was rendered — the trail, since a count
 * updater bumps `updated_at` without anybody editing; opt in by spreading {@see static::getStaleSaveRules()} into
 * `rules()`.
 *
 * @phpstan-require-implements \Hirtz\Skeleton\Models\Interfaces\StaleSaveInterface
 */
trait StaleSaveTrait
{
    /**
     * `null` checks nothing.
     */
    public ?int $loadedAt = null;

    /**
     * Wins over `loadedAt`, which cannot tell two saves within one second apart.
     */
    public ?int $loadedVersion = null;

    public function getLoadedAt(): ?int
    {
        return $this->loadedAt;
    }

    public function getLoadedVersion(): ?int
    {
        return $this->loadedVersion;
    }

    /**
     * `0` before the record's first update.
     */
    public function getStaleSaveVersion(): int
    {
        return (int)$this->findUpdateTrails()->max('id');
    }

    /**
     * @return list<array<int|string, mixed>>
     */
    protected function getStaleSaveRules(): array
    {
        return [
            [
                ['loadedAt', 'loadedVersion'],
                'integer',
            ],
            [
                ['loadedAt'],
                $this->validateStaleSave(...),
                'skipOnEmpty' => false,
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

        $this->addError('loadedAt', Yii::t('skeleton', 'MODEL_STALE_ERROR', [
            'user' => $user instanceof User ? $user->getUsername() : '',
            'date' => Yii::$app->getFormatter()->asDate($trail->created_at, 'short'),
            'time' => Yii::$app->getFormatter()->asTime($trail->created_at, 'short'),
        ]));
    }

    /**
     * By version, or else by the second the record was read in, which misses a save within that same second.
     */
    public function findUpdateTrailSince(): ?Trail
    {
        if ($this->getIsNewRecord() || ($this->loadedVersion === null && !$this->loadedAt)) {
            return null;
        }

        return $this->findUpdateTrails()
            ->andWhere($this->loadedVersion !== null
                ? ['>', 'id', $this->loadedVersion]
                : ['>', 'created_at', gmdate('Y-m-d H:i:s', (int)$this->loadedAt)])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->one();
    }

    /**
     * @return ActiveQuery<Trail>
     */
    protected function findUpdateTrails(): ActiveQuery
    {
        return Trail::find()->where([
            'model_class' => $this->getTrailBehavior()->modelClass,
            'model_id' => implode('-', array_map(strval(...), $this->getPrimaryKey(true))),
            'type' => Trail::TYPE_UPDATE,
        ]);
    }
}
