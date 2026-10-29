<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Queries\TranslationQuery;
use Hirtz\Skeleton\Models\Translation;

interface TranslationInterface extends I18nAttributeInterface
{
    /**
     * The canonical base class, never `static::class`, since the container may resolve a subclass and the record has
     * to stay visible from both.
     *
     * @return class-string
     */
    public function getTranslationModelClass(): string;

    /**
     * Excludes those stored elsewhere, e.g. a permalink slug.
     *
     * @return list<string>
     */
    public function getTranslationAttributes(): array;

    /**
     * @return array<string, array{string, string}>
     */
    public function getTranslatedAttributeNames(): array;

    /**
     * @return list<string>
     */
    public function getTranslationLanguages(): array;

    /**
     * @return TranslationQuery<Translation>
     */
    public function getTranslations(): TranslationQuery;

    /**
     * @param list<string>|null $languages
     */
    public function populateTranslationAttributes(?array $languages = null): void;

    /**
     * @param list<string> $languages
     */
    public function markTranslationsLoaded(array $languages): void;

    public function resetLoadedTranslations(): void;

    /**
     * Called by {@see \Hirtz\Skeleton\Db\ActiveRecord::afterSave()}, before the event the trail listens to.
     *
     * @return array<string, string|null>
     */
    public function saveVirtualAttributes(): array;

    public function deleteVirtualAttributes(): void;
}
