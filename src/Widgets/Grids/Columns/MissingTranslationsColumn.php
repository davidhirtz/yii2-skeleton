<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids\Columns;

use Hirtz\Skeleton\Helpers\StringHelper;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Widgets\Icon;
use Stringable;
use Yii;

/**
 * A warning for a record lacking a translation ({@see I18nAttributeInterface::getMissingTranslationLanguages()}),
 * naming the languages in its tooltip. The column shows only while a record on the page lacks one.
 */
class MissingTranslationsColumn extends Column
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->bodyAttributes = ['class' => 'text-center'];
        $this->content = $this->getIcon(...);
        $this->visible = $this->hasMissingTranslations(...);

        parent::__construct($config);
    }

    protected function hasMissingTranslations(): bool
    {
        foreach ($this->grid->provider->getModels() as $model) {
            if ($model instanceof I18nAttributeInterface && $model->getMissingTranslationLanguages()) {
                return true;
            }
        }

        return false;
    }

    protected function getIcon(mixed $model): ?Stringable
    {
        $languages = $model instanceof I18nAttributeInterface ? $model->getMissingTranslationLanguages() : [];

        if (!$languages) {
            return null;
        }

        $i18n = Yii::$app->getI18n();
        $codes = array_map(static fn (string $language): string => strtoupper($i18n->getLanguageCode($language)), $languages);

        return Icon::make()
            ->name('exclamation-triangle')
            ->addClass('text-warning')
            ->tooltip(Yii::t('skeleton', 'GRID_MISSING_TRANSLATIONS', ['languages' => StringHelper::enumerate($codes)]));
    }
}
