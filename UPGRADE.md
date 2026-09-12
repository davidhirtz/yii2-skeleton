# Upgrade Guide

## 3.0.0 — Fulltext search

`M260913120000Search` creates a `search` table: one row per searchable record, id and language, with the model's
display name in `title`, its searchable attributes in `content` and two fulltext indexes over them. The admin gets a
search button in the navbar and a results page; the frontend gets the query and the DTO, the result page is the
project's.

### Opting a model in

```php
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;

class Product extends ActiveRecord implements SearchableInterface
{
    use SearchableTrait;

    public function getSearchAttributes(): array
    {
        return ['name', 'subtitle', 'description'];
    }

    public function getSearchWeight(): float
    {
        return 0.6;
    }

    protected function isSearchResultVisible(): bool
    {
        return Yii::$app->getUser()->can(static::AUTH_PRODUCT_UPDATE);
    }
}
```

That is the whole opt-in: `Db\ActiveRecord::behaviors()` attaches `Behaviors\SearchBehavior` to every
`SearchableInterface`, and the trait implements the rest of the interface. The attributes are read through
`getI18nAttribute($name, $language, fallback: true)`, so translated and custom attributes come for free. Register
the class from the bundle's `Bootstrap`, the same way media registers its asset classes — nothing is discovered by
scanning:

```php
$app->extendComponent('search', [
    'models' => [
        Product::class,
    ],
]);
```

`models` also takes a weight per class (`[Product::class => 0.9]`), which overrides `getSearchWeight()` for that
project. The weight is stored in the row and multiplied into the score, so changing it needs a `search/rebuild`.

The hooks the trait leaves to the model: `getSearchTitle()` (defaults to the `name` attribute, then
`getTrailModelName()`), `getSearchWeight()` (`1.0`), `getSearchTenantId()` (the `tenant_id` column, else `null`),
`getSearchStatus()` (the `status` column of a `StatusAttributeInterface`), `isSearchable()` (`true` — return
`false` for a record that has no page) and `isSearchResultVisible()`. The last one is the permission check: the
model knows its own `AUTH_*` constants, the search does not, and a `getSearchResult()` of `null` hides the hit
from the current user.

### Keeping the index

`SearchBehavior` writes on insert, update and delete, and skips an update that changed none of the searchable
attributes, `status`, `type` or `tenant_id`. Writes that bypass `save()` leave the index stale — `updateAll()`,
`updateAttributes()`, `batchInsert()` and the `parent_status` propagation in cms — the same limitation the
`translation` table already lives with. Reconcile with the console:

```bash
./yii search/rebuild
./yii search/rebuild --models=Entry,Category
./yii search/clear
```

### The feature flag

`Modules\Admin\Module::$enableSearch` switches the whole feature off in one place: the navbar button, both admin
actions (404), the behavior's writes and the console commands. A project that turns it off should run
`search/clear` once.

### Traps

- **InnoDB fulltext does not see uncommitted rows.** A test that asserts on a `MATCH` has to commit its rows and
  delete them by hand, as `Tests\Search\SearchQueryTest` does; one that reads the index row with an ordinary
  `WHERE` stays inside the test transaction.
- **Tokens shorter than `innodb_ft_min_token_size` (3) are not indexed**, and neither are InnoDB's English
  stopwords. A query that loses every token that way falls back to `title LIKE`, which is the only path a
  two-character query has.
- **The admin search is never scoped to a tenant.** `tenant_id` and `status` are on the row for the frontend
  presets (`SearchQuery::tenant()`, `enabled()`), where a site must only ever surface its own tenant's records.

## 3.0.0 — `Trail::TYPE_DEFAULT` is a plain message

`Models\Trail` declares its own `TYPE_DEFAULT` (`13`) for a trail that carries nothing but a `message`, and
assigns it in `beforeValidate()`. Before, `TYPE_DEFAULT` was the `1` of `Models\Interfaces\TypeAttributeInterface`
— the same value as `TYPE_CREATE` — so a trail written without a type was filed as a create and rendered as one.
`M260913110000TrailType` moves the `trail.type` column default along. Existing rows keep their type; a project
that inserts trail rows through plain SQL and relied on the old column default has to name `TYPE_CREATE` itself.

## 3.0.0 — `IconFilenameAttributeTrait` replaced by `IconCustomAttribute`

`Models\Traits\IconFilenameAttributeTrait` is gone. `Models\CustomAttributes\IconCustomAttribute` does the same
job without an `icon_filename` column, without rules and without a label of its own — it is a
`SelectCustomAttribute` whose options default to `Helpers\IconHelper::getIconFilenames()` over its `path`:

```php
self::TYPE_LINK => [
    'name' => 'Link',
    'customAttributes' => fn (): array => [
        IconCustomAttribute::make('icon'),
    ],
],
```

That is the `customAttributes` key of the type options, as `Cms\Test\Models\TestSection` declares it. A model
without types overrides `CustomAttributesTrait::getCustomAttributes()` or is handed the list through
`setCustomAttributes()`. The path is configurable where `getIconPath()` used to be overridden:

```php
IconCustomAttribute::make('icon')->path('/images/nav-icons/');
```

| removed                            | replacement                                                       |
|------------------------------------|-------------------------------------------------------------------|
| `$iconFilenameAttribute`           | the custom attribute's own name                                   |
| `getIconFilenameAttributeTraitRules()` / `...AttributeLabels()` | nothing — the custom attribute validates and labels itself |
| `static::getIconFilenames()`       | `IconHelper::getIconFilenames($path)`                             |
| `static::getIconPath()`            | `IconCustomAttribute::path()`                                     |
| `getIcon()`                        | none — concatenate the path and the attribute value               |
| `ICON_FILENAME_ATTRIBUTE_ICON`     | the custom attribute's label, which a project names itself        |

The value moves from its own column into the `custom_attributes` JSON column, so a project that used the trait
needs a migration that copies `icon_filename` into it and drops the column.

## 3.0.0 — Trait rules and attribute labels are wired up by hand

`ModelTrait::getTraitNames()`, `getTraitRules()` and `getTraitAttributeLabels()` are gone. They scanned the
class with reflection and called any method named `get<Trait>Rules()` or `get<Trait>AttributeLabels()` they
found — discovery by naming convention, the same magic `Widgets\Attributes\Configure` was removed for. It also
never worked through inheritance: `ReflectionClass::getTraitNames()` is not recursive, so a trait used by a base
class contributed nothing and said nothing about it.

A trait that contributes rules or labels is now called by the class that uses it, as
`Widgets\Grids\Toolbars\GridToolbar` calls `Widgets\Traits\StickyTrait::addStickyClass()`. In this bundle only
`IconFilenameAttributeTrait` used the mechanism and it is removed outright, see above; for `yii2-cms` see
`bundles/yii2-cms/UPGRADE.md`.

Forgetting the `rules()` spread leaves an attribute neither safe nor validated, and
`Widgets\Forms\Fields\Field` renders nothing for an attribute that is not safe — the field disappears from the
form without an error.

## 3.0.0 — `User` profile attributes removed

Six columns are gone from `user`: `picture`, `first_name`, `last_name`, `birthdate`, `city` and `country`.
`M260913100000UserAttributes` adds a `custom_attributes` column, copies the five non-picture columns into it
and then drops all six.

`Models\User` is a `CustomAttributeInterface` now, so a project that still needs any of those five declares it
as a custom attribute and gets the value, the form field, the validation and the trail entry back under the
same attribute name.

### Profile pictures are gone for good

There is no replacement. `User::$picture`, `Models\Forms\UserPictureForm`, `User::deletePicture()`,
`getPictureUrl()`, `getUploadPath()` / `setUploadPath()`, `UserFormTrait::$upload` and
`UserFormTrait::uploadUserPicture()` were removed, together with the `account/picture` and
`user/delete-picture` actions. v3 had no upload UI left for them.

The migration drops the column but touches nothing on disk — delete `web/uploads/users/` by hand once you
have migrated. If you need avatars, `User::getInitials()` still returns the first two characters of the
username.

### What else is gone

| Removed | Replacement |
|---|---|
| `User::getFullName()` | — (compose it from your own definitions) |
| `User::getCountries()` | `Helpers\CountryList::getNames()` |
| `UserActiveFormTrait::getFirstNameField()`, `getLastNameField()`, `getCityField()`, `getCountryField()` | `UserActiveFormTrait::getUserCustomAttributeFields()` |
| The message keys `USER_FIRST_NAME_LABEL`, `USER_LAST_NAME_LABEL`, `USER_BIRTHDATE_LABEL`, `USER_CITY_LABEL`, `USER_COUNTRY_LABEL`, `USER_PICTURE_LABEL`, `USER_UPLOAD_LABEL` | your own `label()` on the definition |

`User::getInitials()` no longer builds the initials from `first_name` / `last_name`; it returns the first two
characters of the username. `UserQuery::matching()` searches `name` and `email` only, and
`nameAttributesOnly()` / `selectListAttributes()` stopped selecting the dropped columns.

### Data upgrade plan

The migration never loses data — it copies every non-empty value of the five columns into `custom_attributes`
keyed by the column name, and a key no definition claims stays in the JSON untouched across saves. But an
undeclared key is invisible: it is not an attribute, not a form field, and not readable from the model. So
**declare the definitions before you migrate**, and the values are there the moment the migration finishes.

1. **Decide which of the five you still need.** For each one, add a definition to `User` through the
   container, in `config/local.php` or wherever you configure it:

   ```php
   use Hirtz\Skeleton\Helpers\CountryList;
   use Hirtz\Skeleton\Models\CustomAttributes\SelectCustomAttribute;
   use Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute;
   use Hirtz\Skeleton\Models\User;

   return [
       'container' => [
           'definitions' => [
               User::class => [
                   'customAttributes' => [
                       TextCustomAttribute::make('first_name')
                           ->label(Yii::t('app', 'First name'))
                           ->max(50),
                       TextCustomAttribute::make('last_name')
                           ->label(Yii::t('app', 'Last name'))
                           ->max(50),
                       TextCustomAttribute::make('city')
                           ->label(Yii::t('app', 'City'))
                           ->max(50),
                       SelectCustomAttribute::make('country')
                           ->label(Yii::t('app', 'Country'))
                           ->options(CountryList::getNames()),
                   ],
               ],
           ],
       ],
   ];
   ```

   Keep the column name as the definition name — that is the key the migration writes. There is no date
   definition, so `birthdate` becomes a `TextCustomAttribute`; its stored value is the `YYYY-MM-DD` string the
   `DATE` column held.

   The definitions only resolve once the columns are gone: a custom attribute whose name collides with a
   column of the same name throws `InvalidConfigException`. That is expected — configure them, then migrate,
   and do not load the admin in between.

2. **Run the migration.**

   ```bash
   ./yii migrate
   ```

3. **Check a record.** `$user->first_name` reads through the JSON column again, the account and user forms
   render one field per definition, and the trail logs the attribute rather than the JSON column.

4. **Anything you did not declare** stays in `custom_attributes` as a plain key. Declare it later and it
   reappears; drop the key with an `UPDATE … JSON_REMOVE(…)` if you are sure you do not want it.

5. **Reverting** (`migrate/down 1`) recreates the five columns, copies the values back out of the JSON and
   drops `custom_attributes` — including any other custom attribute you declared on `User`. `picture` comes
   back as an empty column.

### Your own code

- A query that selected one of the dropped columns, or a `where` on `country` / `city`, has to be rewritten
  against `custom_attributes` (`JSON_EXTRACT`) or dropped. The custom attributes are not indexable as columns.
- `UserActiveForm` and `AccountActiveForm` render `...$this->getUserCustomAttributeFields()` where the four
  fields used to be. A form of your own that called `getFirstNameField()` and friends switches to the same
  method; it returns one field per visible definition, in definition order.
- `Widgets\Forms\Traits\CustomAttributeFieldsTrait::getCustomAttributeFields()` gained an optional model
  parameter for this — pass the record when the form's own model wraps it, as `UserForm` wraps `User`.

## 3.0.0 — `yiisoft/yii2-authclient` removed

Social login is gone. `yiisoft/yii2-authclient` is no longer a dependency, and `M260912130000AuthClient`
drops the `auth_client` table together with every trail that pointed at one of its records — a trail whose
class cannot be resolved logs an error on every trail index, so it is deleted rather than orphaned. The
migration is reversible: `safeDown()` recreates the empty table, its `user_id` index and its foreign key.

Existing `user_login` rows of type `facebook` are kept and the login history renders them exactly as it did
before — `facebook` was never one of `UserLogin::getTypes()`, so nothing about that row changes.

### What is gone

| Removed | Replacement |
|---|---|
| `Auth\Clients\ClientInterface`, `Auth\Clients\Facebook` | — |
| `Models\AuthClient` | — |
| `Models\Forms\AuthClientSignupForm` | `Models\Forms\SignupForm` |
| `Modules\Admin\Widgets\Grids\AuthClientGridView` | — |
| `Modules\Admin\Widgets\Panels\AuthClientListGroup` | — |
| `Web\Application::getAuthClientCollection()`, the `authClientCollection` component | — |
| `Base\Traits\ApplicationTrait::setFacebookClientComponent()` | — |
| `Models\User::getAuthClients()` and the `authClients` relation | — |
| `LoginForm::$enableFacebookLogin`, `LoginForm::isFacebookLoginEnabled()` | — |
| `SignupForm::$enableFacebookSignup`, `SignupForm::isFacebookSignupEnabled()` | — |
| The `account/auth` action (`yii\authclient\AuthAction`) and `AccountController::onAuthSuccess()` | — |
| `AccountController::actionDeauthorize()`, `UserController::actionDeauthorize()` | — |

The message keys `AUTH_CLIENT_*`, `ACCOUNT_CONFIRM_REMOVE`, `ACCOUNT_SUCCESS_ACCOUNT_NOW_CONNECTED`,
`ACCOUNT_SUCCESS_REMOVED`, `ACCOUNT_SUCCESS_SIGN_UP_COMPLETED_CLIENT`, `ACCOUNT_SUCCESS_WELCOME_BACK` and
`USER_SUCCESS_REMOVED` were dropped from every language file.

### Migrating a downstream project

1. **Before `./yii migrate`**, export `auth_client` if you want to keep the linked account ids — the
   migration drops the table and there is nothing left to read afterwards.
2. Remove `params['facebookClientId']` and `params['facebookClientSecret']` from `config/params.php`.
   They no longer register anything; left in place they are dead configuration.
3. Remove any `components.authClientCollection` entry from your config, and any client class of your own
   that implemented `Auth\Clients\ClientInterface`.
4. Drop `yiisoft/yii2-authclient` from your own `composer.json` if you required it directly.
5. A user who only ever signed in through a client has no password. Point them at
   `account/recover` to set one — the recovery mail works for any confirmed email address.

## 3.0.0 — Tenants

`yii2-cms` requires `yii2-tenant`. Every database has at least one tenant, every entry belongs to one,
and `yii2-cms-tenant` is gone: its behaviour lives in `yii2-cms` and `yii2-tenant`, and its GitHub
repository is archived.

`permalink` became an entry-only table on the way, and category URLs were removed.

### Everyone

- **Category URLs are gone.** `Module::$enableCategoryUrls` and the `site/category` view no longer exist.
  `Category::getRoute()` returns `['/cms/site/index', 'category' => <slug>]`, the filtered entry index,
  which was already the shipped default. If you had the option on and those URLs were public, add
  redirect rules for them before upgrading. The category `slug` column and its uniqueness rule stay.
- **`permalink` is entry-only.** `model_class` / `model_id` are `entry_id`, with an `ON DELETE CASCADE`
  foreign key, and the table carries the entry's `tenant_id`. The unique indexes are
  `(tenant_id, language, uri)` and `(entry_id, language)`.
- **`Models\Interfaces\PermalinkInterface` and `Models\Actions\DeletePermalinks` are gone**, and so is
  `permalink/prune` — the cascade keeps the table consistent. `permalink/rebuild` stays.
  `Models\Traits\PermalinkTrait` is typed against `Models\Entry`.
- **`Models\Queries\PermalinkQuery::whereModel()` is gone.** Filter on `entry_id`, or read
  `$entry->permalinks`. On `EntryQuery`, `whereSlug()` is `whereUri(string $uri, ?string $language = null)`
  and `whereNotSlug()` is `whereNotUri()`; both join the permalink table and honour the
  `Permalink::LANGUAGE_ALL` fallback, which the old slug subquery did not.
- **Redirects are host-qualified.** A `Redirect` whose `request_uri` names a host (`www.example.com/old`) only
  fires on that host, and the 404 handler prefers it over a bare-path record for the same path. Renaming an
  entry records the host-qualified form with the entry's tenant host, so two tenants can rename the same slug
  independently. Redirects you enter in the admin may use either form. The host compared is the tenant's
  canonical one, which `Tenant\Web\UrlManager` now keeps as `hostInfo` on every request, including a draft
  request and a request on a host that only fell back to the default tenant — absolute URLs there are now on
  the tenant's host rather than the request's.
- **`Controllers\SiteController`** lost `findPermalink()`, `renderPermalink()`, `renderCategory()`,
  `findCategory()`, `validateCategoryResponse()` and `findCategoryEntries()`. `actionView()` resolves the
  entry through `getQuery()->whereUri($slug)`; override `findEntry()` to change the lookup.

### A project that had no tenant bundle

The migration seeds exactly one tenant and gives every entry and permalink its id.

1. **Before `./yii migrate`**, set the seed URL for *this environment* — `params['tenantUrl']`, or the
   console `urlManager.hostInfo`. `Cms\Migrations\M260908100000Tenant` refuses to run without one and
   never guesses. `params['tenantUrl']` is the documented source; the URL manager reads it back as
   `hostInfo` on every request, so a staging copy of a production database needs its own value.
2. After `./yii migrate`, assert: `tenant` has one enabled row, `entry.tenant_id` is NOT NULL, and no
   entry and no permalink has a NULL `tenant_id`.
3. Unless you want the tenant admin, add `'tenant' => ['enableAdminModule' => false]` under `modules`.
   With it off the routes 404 rather than hiding a nav item over a live controller, and the tenant URL
   can then only be changed through the console or SQL.
4. `Tenant::AUTH_*` RBAC items are still created by the tenant bundle's `Roles` migration. They are
   harmless and are not offered in the dashboard role editor while the admin module is off.

### A project that had `yii2-cms-tenant`

Remove `davidhirtz/yii2-cms-tenant` from `require`; `yii2-tenant` arrives with `yii2-cms`. A project
`Entry` extends `Hirtz\Cms\Models\Entry` again. The class map:

| v2 (`davidhirtz\yii2\cms\tenant\…`) | v3 |
|---|---|
| `models\Entry` | `Hirtz\Cms\Models\Entry` |
| `models\Permalink`, `models\queries\PermalinkQuery` | removed — `Hirtz\Cms\Models\Permalink` carries `tenant_id` |
| `models\queries\EntryQuery` | `Hirtz\Cms\Models\Queries\EntryQuery` |
| `data\EntryActiveDataProvider` | `Hirtz\Cms\Modules\Admin\Data\EntryActiveDataProvider` |
| `filters\PageCache` | `Hirtz\Tenant\Filters\PageCache` |
| `modules\admin\widgets\forms\EntryActiveForm` | `Hirtz\Cms\Modules\Admin\Widgets\Forms\EntryActiveForm` |
| `modules\admin\widgets\forms\fields\EntryParentIdSelectField` | `Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\EntryParentIdSelectField` |
| `modules\admin\widgets\forms\fields\TenantIdField` | `Hirtz\Cms\Modules\Admin\Widgets\Forms\Fields\TenantIdField` |
| `modules\admin\widgets\grids\EntryGridView` | `Hirtz\Cms\Modules\Admin\Widgets\Grids\EntryGridView` |
| `modules\admin\widgets\grids\SectionParentEntryGridView` | `Hirtz\Cms\Modules\Admin\Widgets\Grids\SectionParentEntryGridView` |
| `modules\admin\widgets\grids\TenantGridView` | `Hirtz\Cms\Modules\Admin\Widgets\Grids\TenantGridView` |
| `validators\TenantIdValidator` | `Hirtz\Cms\Validators\TenantIdValidator` |
| `behaviors\EntryTenantBehavior`, `behaviors\TenantEntryBehavior` | removed — the methods are on `Entry`, so `getEntryTenantBehavior()->getTenantRouteParams()` is `getTenantRouteParams()` |
| the two admin traits, `TenantDropdownAssetBundle` | removed |

Nothing is bound in the container any more except `Tenant\Modules\Admin\Widgets\Grids\TenantGridView`,
which `Cms\Bootstrap` maps to the cms subclass that adds the entry-count column. Grep `config/` for
`cms\tenant` afterwards: a DI definition that named the glue `Entry` as a *string* is invisible to Rector.

After `./yii migrate`: no seed is inserted, `entry.tenant_id` only becomes NOT NULL, and `permalink` is
built with each entry's tenant. If any entry has a NULL `tenant_id` and you have several tenants the
migration aborts and names the count — assign them by hand first. Two behaviour notes: two tenants may
now serve the same slug, which v2 silently lost to the first; and category URLs never worked on a
tenanted site, because the lookup filtered on a tenant the category did not have.

## 3.0.0 — `model_class`

Every polymorphic table names its owner in a `model_class` / `model_id` pair. `model` was the natural
name for a relation returning the owning record itself, so the column that holds the class string
gave it up.

`M260912090000ModelClass` renames `trail.model` and `translation.model`, recreates their indexes
under the new name, and rewrites the `model` key of `trail.data` that the `TYPE_CHILD_*` types write.
It is idempotent, so a fresh install runs it as a no-op. `permalink` has no class column of any name —
see "3.0.0 — Tenants" below, which made it an entry-only table.

Your own polymorphic tables are your own business — nothing here touches them.

### Renames

| Before | After |
|---|---|
| `Models\Trail::$model` | `Models\Trail::$model_class` |
| `Models\Trail::getModelClass()` | `getModelRecord()` |
| `Models\Trail::getDataModelClass()` | `getDataModelRecord()` |
| `Models\Collections\TrailModelCollection::getModelByNameAndId()` | `getModelByClassAndId()` |
| `Models\Translation::$model` | `Models\Translation::$model_class` |

The two `getModel…Class()` methods returned the *record*, not a class — hence the new names.
`TrailBehavior::$modelClass` and `TranslationInterface::getTranslationModelClass()` already said
class and are unchanged.

The `/admin/trail/index?model=` query parameter is a URL, not storage, and keeps its name.

### What to check in your own code

Anything that writes or filters those columns by hand: `Trail::updateAll()` / `deleteAll()`,
`Translation::deleteAll()` in a test `tearDown()`, raw `[[model]]` SQL, fixture data files, and
`andOnCondition()` on a relation to one of the three tables.

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

## 3.0.0 — Key-based translations

v3 switches every platform message source to **key-based translations** resolved with
`forceTranslation => true`. Instead of the English sentence being both the lookup key and the
fallback, each string now has a stable, uppercase, domain-first **key**, and the English text lives
in the `en-US` message file like any other language. Call sites stay on `Yii::t()` — the `yii
message` extractor only recognises that call.

```php
// before
Yii::t('cms', 'Create Entry');

// after
Yii::t('cms', 'ENTRY_CREATE_BUTTON');
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
no tool can reliably invent semantic keys from arbitrary English, so the key assignment is manual
(best done per model/feature).

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
   Yii::t('app', 'PRODUCT_NAME_LABEL');
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

A partially migrated app runs correctly: un-migrated calls keep passing the English string, which
`forceTranslation` returns unchanged when no key matches. Migrate incrementally, one category or
feature at a time.
