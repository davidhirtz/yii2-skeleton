<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Widgets\Grids;

use Hirtz\Skeleton\Html\Div;
use Hirtz\Skeleton\Models\Interfaces\I18nAttributeInterface;
use Hirtz\Skeleton\Widgets\Widget;
use Override;
use Stringable;
use Yii;

/**
 * "Missing in: DE, FR" beneath a record's name in a grid, for the languages it lacks a translation in
 * ({@see I18nAttributeInterface::getMissingTranslationLanguages()}); nothing while it lacks none.
 */
class MissingTranslations extends Widget
{
    protected ?I18nAttributeInterface $model = null;

    public function model(?I18nAttributeInterface $model): static
    {
        $this->model = $model;
        return $this;
    }

    #[Override]
    protected function renderContent(): string|Stringable
    {
        $languages = $this->model?->getMissingTranslationLanguages() ?? [];

        if (!$languages) {
            return '';
        }

        $i18n = Yii::$app->getI18n();
        $codes = array_map(static fn (string $language): string => strtoupper($i18n->getLanguageCode($language)), $languages);

        return Div::make()
            ->class('small text-muted')
            ->text(Yii::t('skeleton', 'GRID_MISSING_TRANSLATIONS', ['languages' => implode(', ', $codes)]));
    }
}
