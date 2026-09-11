<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Queries\TranslationQuery;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;

/**
 * Implemented via {@see TranslationTrait}.
 */
interface TranslationInterface extends I18nAttributeInterface
{
    /**
     * The canonical base class, never `static::class`: the container may resolve a subclass, and the record has to
     * stay visible from both.
     *
     * @return class-string
     */
    public function getTranslationModelClass(): string;

    /**
     * @return list<string> `i18nAttributes` minus those stored elsewhere, e.g. a permalink slug
     */
    public function getTranslationAttributes(): array;

    /**
     * @return array<string, array{string, string}> attribute and language per translated attribute name
     */
    public function getTranslatedAttributeNames(): array;

    /**
     * @return list<string> the attributes without a column
     */
    public function getVirtualAttributes(): array;

    /**
     * @return list<string>
     */
    public function getColumnAttributes(): array;

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

    public function updateOldVirtualAttributes(): void;

    /**
     * Called by {@see \Hirtz\Skeleton\Db\ActiveRecord::afterSave()}, before the event the trail listens to.
     *
     * @return array<string, string|null> the previous value per changed virtual attribute name
     */
    public function saveVirtualAttributes(): array;

    public function deleteVirtualAttributes(): void;
}
