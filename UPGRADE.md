# Upgrade Guide

## 3.0.0 — Custom attributes

A model can declare typed attributes that have no column of their own. Their values are ordinary
attributes — `$section->subtitle`, `load()`, `validate()`, the trail, `getI18nAttribute()` — but they
are stored together in one `custom_attributes` JSON column.

### Opting a model in

```php
use Hirtz\Skeleton\Models\Interfaces\CustomAttributeInterface;
use Hirtz\Skeleton\Models\Traits\CustomAttributesTrait;

class Section extends ActiveRecord implements CustomAttributeInterface
{
    use CustomAttributesTrait;
}
```

Every platform model that ships with a `type` already carries the column and the trait. A model of
your own needs the column (`MigrationTrait::addCustomAttributesColumn()`), and its `rules()`,
`attributeLabels()` and `attributeHints()` must spread `parent::…` — that is where the definitions
inject theirs.

Add a `@property` docblock per definition so static analysis and the IDE know them:

```php
/**
 * @property string|null $subtitle
 * @property string|null $subtitle_de
 */
```

### Declaring definitions

The default `getCustomAttributes()` reads the `customAttributes` key of the model's type options.
Prefer a closure: `getTypes()` is called often and building the objects for every type on each call
is waste.

```php
public static function getTypes(): array
{
    return [
        self::TYPE_HEADLINE => [
            'name' => 'Headline',
            'customAttributes' => fn (): array => [
                TextCustomAttribute::make('subtitle')->translatable(),
            ],
        ],
        self::TYPE_LINK_LIST => [
            'name' => 'Link list',
            'customAttributes' => fn (): array => [
                GroupCustomAttribute::make('links')
                    ->multiple()
                    ->maxCount(5)
                    ->attributes([
                        TextCustomAttribute::make('label')->translatable(),
                        UrlCustomAttribute::make('url')->required(),
                    ]),
            ],
        ],
    ];
}
```

The definitions can also be configured through the container, like `i18nAttributes`; a configured
list (or closure) replaces the type options entirely:

```php
'container' => [
    'definitions' => [
        Section::class => [
            'customAttributes' => [
                BooleanCustomAttribute::make('featured'),
            ],
        ],
    ],
],
```

A model whose definitions depend on something else overrides `getCustomAttributes()`, and
`getCustomAttributesKey()` with whatever the definitions are derived from — the resolved definitions
are cached until that key changes.

Shipped types: `Text`, `Html`, `Boolean`, `Number`, `Select`, `Icon`, `Url`, `Email`, `HexColor` and
`Group`, all suffixed `CustomAttribute`. Each takes `label()`, `hint()`, `translatable()`,
`required()`, `visible()`, `disabled()` and `default()`; the last four accept a closure taking the
owning model.

- `visible: false` — no rule at all: unsafe, unvalidated, not rendered. The stored value is kept.
- `disabled: true` — unsafe and unvalidated, but still rendered as a disabled input.

### Rules for the author

- `getCustomAttributes()` must be cheap and free of side effects, and must not trigger a lazy
  relation query: it runs in `afterFind()` for every loaded record. Eager load the relation
  (`->with('file')`) or guard with `isRelationPopulated()`.
- A definition name must match `^[a-z][a-z0-9_]*$`, be unique, and collide with neither a column nor
  a translated attribute name. A model that declares definitions but whose table lacks the
  `custom_attributes` column is a configuration error as well. All of these throw an
  `InvalidConfigException` when the definitions resolve.

### Translations

A translatable definition keeps the usual names — `subtitle` for the source language, `subtitle_de`
for the rest — but both live inside the JSON, not in the `translation` table: a repeatable group item
has no stable flat name to key a translation row by. Everything else is unchanged:
`getI18nAttribute('subtitle', 'de', fallback: true)` works and the trail labels it "Subtitle (DE)".

### Stored JSON

The column holds an object keyed by attribute name. A key no current definition claims is kept, so
switching a type back does not lose its values. A value that serializes to `null` has no key, and an
empty object is stored as `null`.

Two documented limits, the same as for translations: `updateAttributes()` and `batchInsert()` write
columns directly and therefore bypass the JSON.

### Forms

`ActiveForm` subclasses append `...$this->getCustomAttributeFields()` (from
`Widgets\Forms\Traits\CustomAttributeFieldsTrait`) to their fieldset and render the type select
with `Widgets\Forms\Fields\TypeSelectField`. When the types render different fields it reloads the
form through htmx before the fields can change, and does nothing otherwise; the action tells that
request apart with `Request::isFormReload()` and skips the save:

```php
if ($section->load($post) && !$this->request->isFormReload()) {
    // …
}
```

An action of your own on an opted-in model needs that guard, or it saves on every type change.

## 3.0.0 — Translations move to the `translation` table

A translated attribute used to be one real column per language: `name` for the source language,
`name_de`, `name_fr`, … for the others. Adding a language meant a migration on every translated
table. In v3 the source language stays in its column and every other language moves to a single
generic `translation` table, keyed by `model`, `model_id`, `language` and `attribute`.

The attribute names do not change. `name_de` is still what a form posts, what a rule validates, what
the trail logs and what `getI18nAttributeName('name', 'de')` returns — it is a virtual attribute now,
reported by `attributes()` but read from and written to the `translation` table.

```php
// before — one column per language, migration required to add one
$entry->name_de;

// after — unchanged, loaded from the translation table on first access
$entry->name_de;
```

### Migrating a downstream project

1. Run the migrations. Each bundle ships one that moves its own models' `_xx` columns into
   `translation` and drops them; the skeleton's `M260910100000Translation` creates the table and must
   run first.
2. For every translated model the project owns, add the interface and the trait. No behavior is
   involved: `Db\ActiveRecord` writes the virtual attributes itself, before the event `TrailBehavior`
   listens to, so the trail sees the translated values whatever order the behaviors were attached in.

```php
use Hirtz\Skeleton\Models\Interfaces\TranslationInterface;
use Hirtz\Skeleton\Models\Traits\I18nAttributesTrait;
use Hirtz\Skeleton\Models\Traits\TranslationTrait;

class Product extends ActiveRecord implements TranslationInterface
{
    use I18nAttributesTrait;
    use TranslationTrait;

    public function getTranslationModelClass(): string
    {
        // Never `static::class` — the container resolves the model class to whatever the application
        // configured, and keying on the runtime class hides the record from the other path.
        return self::class;
    }
}
```

A model that overrides `saveVirtualAttributes()` returns the previous value per changed virtual
attribute name, merged into the changed attributes the trail records.

3. Write a migration for the project's own models, using the helpers on
   `Hirtz\Skeleton\Db\Traits\MigrationTrait`:

```php
public function safeUp(): void
{
    $this->moveI18nColumnsToTranslations(Product::create());
}

public function safeDown(): void
{
    $this->restoreI18nColumnsFromTranslations(Product::create());
}
```

`safeDown()` does not recreate indexes; a migration whose `safeUp()` dropped a unique index on a
translated column recreates it itself.

4. A model that stores translations must return an `I18nActiveQuery` (or a subclass) from `find()` —
   that is what keeps the virtual names out of the SELECT and rewrites them in `orderBy()`.

### `replaceI18nAttributes()` becomes `withTranslations()`

`I18nActiveQuery::replaceI18nAttributes()` rewrote the SELECT to the current language's columns.
There are no such columns anymore, so it is replaced by `withTranslations()`, which eager loads the
translation records of the given languages, every configured language by default:

```php
// before
Entry::find()
    ->selectSiteAttributes()
    ->replaceI18nAttributes();

// after
Entry::find()
    ->selectSiteAttributes()
    ->withTranslations();

// one language only, for a list that is read in that language and discarded
Entry::find()->withTranslations('de');
```

A query that returns more than one row calls `withTranslations()` on its own when nothing was decided,
so a list — a grid, a cached collection, a sitemap — never queries once per record in a language it is
read in later. `withoutTranslations()` opts out for a list whose translated attributes are not read; a
single record stays lazy and loads every language on first access.

### What to keep in mind

- `i18nAttributes` is still the switch. It decides which attributes are translated, and it has to be
  set before the model's attributes are first touched — configure it through the DI container or in
  `init()`, as before.
- **Translated attributes must be string-typed.** `translation.value` is a `TEXT` column, so a number
  would come back as a string after a reload.
- **An empty string, `null` and a missing record all mean "no translation."** A write normalises `''`
  to deleting the record, a read returns `null`, and the SQL fallback uses `NULLIF(value, '')`. The
  typecast behavior no longer turns `''` into `null` for these attributes — they are not columns.
- **The migration only moves the languages the running application configures.** A `_xx` column of a
  language that was removed from `i18n.languages` earlier is left untouched, as is an attribute that
  was removed from `i18nAttributes`.
- `ActiveRecord::batchInsert()` and `updateAttributes()` write the table directly: the former never
  writes a translation, the latter fails on a translated name.
- Reading a translation in one language loads every unloaded language of that record in one query.

### Language fallback

`getI18nAttributeName()`, `getI18nAttribute()` and their `I18nActiveQuery` counterpart take a third
`bool $fallback = false` argument. With it, an attribute that holds no translation resolves to the
untranslated attribute — on a model that is the source-language value, in SQL a
`COALESCE(NULLIF(…), …)`, which is what makes sorting and searching fall back to the source language:

```php
$entry->getI18nAttribute('name', 'de', fallback: true);
Entry::find()->getI18nAttributeName('name', fallback: true);
```

Uniqueness deliberately does **not** fall back: a translated value competes with the same language
only.

## 3.0.0 — Key-based translations via the `Lang` facade

v3 replaces direct `Yii::t()` calls with the `Hirtz\Skeleton\I18n\Lang` facade and switches every
platform message source to **key-based translations** resolved with `forceTranslation => true`.
Instead of the English sentence being both the lookup key and the fallback, each string now has a
stable, uppercase, domain-first **key**, and the English text lives in the `en-US` message file like
any other language.

```php
// before
Yii::t('cms', 'Create Entry');

// after
Lang::t('cms', 'ENTRY_CREATE_BUTTON');
```

```php
// messages/en-US/cms.php
'ENTRY_CREATE_BUTTON' => 'Create Entry',
// messages/de/cms.php
'ENTRY_CREATE_BUTTON' => 'Eintrag erstellen',
```

### Why

- Editing an English string no longer orphans every translation keyed to the old sentence.
- The same key can diverge per language without the source text drifting.
- `en-US` becomes an explicit, reviewable catalogue of the source copy.
- Keys group by topic, so a translator sees every string for a model or feature contiguously.

### Key convention

Keys are `UPPER_SNAKE_CASE` and **domain-first** — the leading token is the model or functional area,
so translations cluster by topic:

| Kind | Pattern | Example |
| --- | --- | --- |
| Attribute label / hint / error | `{DOMAIN}_{ATTRIBUTE}_LABEL` \| `_HINT` \| `_ERROR` | `ENTRY_NAME_LABEL` |
| Flash message | `{DOMAIN}_FLASH_{SLUG}` | `ENTRY_FLASH_ASSET_ORDER_CHANGED` |
| Button / link | `{DOMAIN}_BUTTON_{SLUG}` | `USER_BUTTON_COPY_LINK` |
| Confirmation dialog | `{DOMAIN}_CONFIRM_{ACTION}` | `USER_CONFIRM_DELETE` |
| RBAC permission description | `AUTH_{PERMISSION}_DESCRIPTION` | `AUTH_ENTRY_CREATE_DESCRIPTION` |
| Nav / menu label | `{DOMAIN}_NAV_{SLUG}` | `USER_NAV_ITEM_USER_MANAGEMENT` |
| Shared / cross-domain string | `COMMON_{SLUG}` | `COMMON_MODEL_ID` |

`COMMON_*` holds strings used across more than one domain (the base `ActiveRecord` labels
`COMMON_ID_LABEL`, `COMMON_STATUS_LABEL`, `COMMON_TYPE_LABEL`, … and generic UI copy). The `Yii::t`
category already scopes the message source, so keys carry **no** bundle prefix.

### What changed in the platform

Every bundle's message source was migrated (`skeleton`, `cms`, `media`, `shopify`, `location`,
`tenant`, `hotspot`, `config`). Each bundle's `Bootstrap` (or `I18N` for `skeleton`) now sets
`forceTranslation => true`, and all `messages/<lang>/<category>.php` files were regenerated with keys.
The `country` (already forced, data-keyed), `yii` (framework) and `app` (host application) categories
were left unchanged.

### Migrating a downstream project

Downstream apps translate their own strings under the **`app`** category. There is no Rector rule:
the mechanical `Yii::t` → `Lang::t` swap is trivial, but no tool can reliably invent semantic keys
from arbitrary English, so the key assignment is manual (best done per model/feature).

1. **Turn on `forceTranslation`** for the `app` message source in your application config:

   ```php
   'i18n' => [
       'translations' => [
           'app' => [
               'class' => \yii\i18n\PhpMessageSource::class,
               'sourceLanguage' => 'en-US',
               'basePath' => '@app/messages',
               'forceTranslation' => true,
           ],
       ],
   ],
   ```

2. **Assign a key** to each string following the convention above and rewrite the call sites:

   ```php
   use Hirtz\Skeleton\I18n\Lang;

   Lang::t('app', 'PRODUCT_NAME_LABEL');
   ```

3. **Regenerate the message files.** Add an `en-US/app.php` mapping each key to its English source
   text, and remap the existing `de/fr/...` files from the old English string to the new key so no
   translation is lost:

   ```php
   // messages/en-US/app.php
   'PRODUCT_NAME_LABEL' => 'Product name',
   // messages/de/app.php
   'PRODUCT_NAME_LABEL' => 'Produktname',
   ```

4. **Verify** every key used in code resolves in `en-US/app.php` before shipping — a missing key
   renders as the key string itself.

`Yii::t()` still works (the facade delegates to it), so a partially migrated app runs correctly:
un-migrated calls keep passing the English string, which `forceTranslation` returns unchanged when no
key matches. Migrate incrementally, one category or feature at a time.
