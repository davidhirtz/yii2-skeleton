<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Traits;

use Hirtz\Skeleton\Db\ActiveQuery;
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

    /**
     * @var int|null the record's version when it was read, see {@see static::getStaleSaveVersion()}; it wins over
     * `loadedAt`, which cannot tell two saves within one second apart
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
     * The id of the record's newest update trail, `0` before its first update.
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
     * The newest update another save wrote after the record was read: by version, or else by the second it was read
     * in, which misses a save within that same second.
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
