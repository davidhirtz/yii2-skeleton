<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Models\Interfaces;

use Hirtz\Skeleton\Models\Queries\TranslationQuery;
use Hirtz\Skeleton\Models\Translation;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;

/**
 * A model whose translated attributes are stored in {@see Translation} records rather than one column per language.
 * Implemented via {@see TranslationTrait}.
 */
interface TranslationInterface extends I18nAttributeInterface
{
    /**
     * The class stored in {@see Translation::$model}, and matched on when reading the records back.
     *
     * It must be the canonical base class, never `static::class`: the container resolves the model class to whatever
     * an application configured, so the very same row belongs to a subclass when a test or a bundle extension builds
     * it. Keying on the runtime class makes the record invisible from the other path.
     *
     * @return class-string
     */
    public function getTranslationModelClass(): string;

    /**
     * The i18n attributes stored in {@see Translation} records. Defaults to all of them; a model whose attribute is
     * backed by another table (a permalink slug) removes it here.
     *
     * @return list<string>
     */
    public function getTranslationAttributes(): array;

    /**
     * @return array<string, array{string, string}> the attribute and language per translated attribute name
     */
    public function getTranslatedAttributeNames(): array;

    /**
     * The attributes reported by {@see \yii\db\ActiveRecord::attributes()} that have no column.
     *
     * @return list<string>
     */
    public function getVirtualAttributes(): array;

    /**
     * The attributes that are actually columns. Queries select these rather than
     * {@see \yii\db\ActiveRecord::attributes()}.
     *
     * @return list<string>
     */
    public function getColumnAttributes(): array;

    /**
     * @return TranslationQuery<Translation>
     */
    public function getTranslations(): TranslationQuery;

    /**
     * Sets every translated attribute of the given languages, and its old attribute, from the `translations`
     * relation. Loads the relation if it is not populated yet.
     *
     * @param list<string>|null $languages
     */
    public function populateTranslationAttributes(?array $languages = null): void;

    /**
     * @param list<string> $languages
     */
    public function markTranslationsLoaded(array $languages): void;

    public function updateOldTranslationAttributes(): void;

    /**
     * @return array<string, string|null> the previously stored value per changed translated attribute name
     */
    public function saveTranslations(): array;

    public function deleteTranslations(): void;
}
