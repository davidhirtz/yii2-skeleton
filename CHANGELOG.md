## 3.0.0 (in development)

- `Db\ActiveQuery::selectAllColumns()` selects the columns, not `attributes()`: a model with virtual attributes
  would otherwise select a column that does not exist. `Db\I18nActiveQuery`'s override of it is gone
- `Widgets\Grids\GridView` no longer uses `Widgets\Traits\ModelTrait`: nothing read its `$model` — a column takes
  its label from the first row of the provider — and the property stood in the way of a subclass naming its own.
  A grid that needs one uses the trait itself. Its template parameter now types `$rowAttributes`
- Added `Models\Traits\VisibleAttributeTrait`, moved here from `Hirtz\Cms\Models\Traits`: `getVisibleAttribute()`
  and `isAttributeVisible()` read the `hiddenFields` key of a model's type options
- Fixed the trail link of a deleted user in `Modules\Admin\Widgets\Grids\TrailGridView`: it joined the class and
  the id with `:` while the trail index splits the parameter on `@`, so the link filtered nothing. Both routes are
  built by the new `getTrailIndexRoute()`, with `getUserTrailRoute()` next to `getTrailModelRoute()`
- The polymorphic `trail` and `translation` tables name their owner in `model_class` instead of `model`
  (`M260912090000ModelClass`), so a model can keep a relation named `model` for the record itself. The JSON key
  `trail.data.model` written by the `TYPE_CHILD_*` types was renamed with it. `Models\Trail::getModelClass()` and
  `getDataModelClass()` returned the record, not a class, and are now `getModelRecord()` and `getDataModelRecord()`;
  `Models\Collections\TrailModelCollection::getModelByNameAndId()` is `getModelByClassAndId()`. See `UPGRADE.md`
- Added custom attributes: a model implementing `Models\Interfaces\CustomAttributeInterface` and using
  `Models\Traits\CustomAttributesTrait` declares typed definitions (`Models\CustomAttributes\*`) whose values are
  ordinary attributes — `load()`, `validate()`, the trail and `getI18nAttribute()` all work — but are stored together
  in one `custom_attributes` JSON column instead of a column each. The definitions are resolved from the model's
  current state, by default from the `customAttributes` key of its type options. Shipped types: `Text`, `Html`,
  `Boolean`, `Number`, `Select`, `Icon`, `Url`, `Email`, `HexColor` and `Group`, the last one bundling definitions
  into a nested object or a repeatable list of them. A translatable definition keeps its `_xx` name but is stored
  inside the JSON, not in the `translation` table. See `UPGRADE.md`
- `Db\ActiveRecord` gained `getCustomAttributesColumn()`, a `rules()` returning the custom rules, `attributeHints()`,
  and the `beforeValidate()` that applies the definition defaults to a new record
- A `GroupCustomAttribute` formats its trail value as a label => value map — one entry per child of every row, numbered
  when the group is `multiple`, with a translated child getting its own and a nested group flattened into the same map
  — instead of a JSON string. `CustomAttribute::formatValue()` may return such a map, which
  `Modules\Admin\Widgets\Grids\TrailGridView` renders as its own rows: attribute and value on a create, attribute,
  old and new on an update, so a group with many attributes reads down rather than across
- A `GroupCustomAttribute` with a `minCount` renders that many rows up front, empty when there is nothing stored, and
  the remove button is hidden while the group is at its minimum. An empty row is still dropped on save, so the minimum
  is reported as a validation error rather than stored as an empty object
- `Widgets\Forms\Fieldset` asks a `CustomAttributeInterface` model for a definition before deriving a field from the
  validators, and no longer drops a disabled field (`Widgets\Forms\Fields\Field::isDisabled()`). Added
  `Widgets\Forms\Fields\GroupField`, `Widgets\Forms\Traits\CustomAttributeFieldsTrait` and
  `Assets\CustomAttributesAssetBundle`
- Added `Widgets\Forms\Fields\Field::reloadsForm()` and `Web\Request::isFormReload()`: a field the rendered form
  depends on posts the form to its own action on change and swaps it with the response, and the marker header keeps
  the action from saving. `Widgets\Forms\Fields\TypeSelectField` applies it to a type select, but only when the
  types render different custom attribute fields (per-type fingerprints on the options and a conditional trigger)
- `CustomAttributeInterface::setCustomAttributes()` takes a list of definitions or a closure returning one, so they
  can be configured through the container like `i18nAttributes`; a configured list replaces the type options
- `Widgets\Forms\Fields\SelectField::multiple()` renders a multiple select: the name ends in `[]` and `selected`
  matches against an array. `itemAttributes()` adds attributes to an option however the item was built
- Added `Db\Traits\MigrationTrait::addCustomAttributesColumn()` and `dropCustomAttributesColumn()`
- Added `Helpers\IconHelper::getIconFilenames()`, which `Models\Traits\IconFilenameAttributeTrait` now delegates to.
  Removed its `findIconFiles()` and `humanizeIconFilename()` methods
- `Models\Traits\I18nAttributesTrait` gained `getI18nAttributes()`, which is `i18nAttributes` plus the translatable
  custom attributes of the model's current state, and is what `isI18nAttribute()`, the labels, hints and rules read
- The virtual attribute plumbing moved from `Models\Traits\TranslationTrait` into `Db\ActiveRecord`, so a second
  feature can add attributes without a column of their own without colliding with translations: `attributes()`,
  `getVirtualAttributes()`, `getColumnAttributes()`, `insertInternal()`, `updateInternal()`, `afterRefresh()` and
  `updateOldVirtualAttributes()` are base class methods now. `TranslationInterface` no longer declares
  `getVirtualAttributes()`, `getColumnAttributes()` and `updateOldVirtualAttributes()`; it declares
  `getTranslationLanguages()` and the new `resetLoadedTranslations()` instead
- `Base\Traits\ModelTrait` caches the validators itself and adds `resetValidators()`, which drops that cache and the
  cached scenarios. `yii\base\Model` keeps its validators in a private property that cannot be invalidated, which
  breaks a model whose rules depend on an attribute assigned after the validators were first built
- `Behaviors\AttributeTypecastBehavior::typecastAttributes()` defaults to the column attributes of a
  `Db\ActiveRecord` owner instead of `attributes()`

- `Db\ActiveRecord::instantiate()` creates loaded records through the container, so a definition configured for
  the model class — `i18nAttributes` in particular — applies to records from `find()` as well as to `create()`.
  Before, only models using `TypeAttributeTrait` did this
- Translated attributes are stored in the new `translation` table instead of one `_xx` column per language, so
  adding a language no longer needs a migration on every translated table. The source language stays in the
  model's own column. Added `Models\Translation`, `Models\Queries\TranslationQuery`,
  `Models\Interfaces\TranslationInterface`, `Models\Traits\TranslationTrait` and
  `Models\Actions\SaveTranslations`. A translated attribute keeps its
  name (`name_de`) but is a virtual attribute now: it is reported by `attributes()`, kept out of the
  INSERT/UPDATE and read lazily on first access. See `UPGRADE.md`
- `Db\ActiveRecord::afterSave()` and `afterDelete()` write and delete the virtual attributes of a
  `TranslationInterface` model, merging their previous values into the changed attributes before the event
  `Behaviors\TrailBehavior` listens to. This replaces `Behaviors\TranslationBehavior`, which had to be
  attached before `TrailBehavior` or the trail silently lost the translated values. The model hooks are
  `saveVirtualAttributes()`, `deleteVirtualAttributes()` and `updateOldVirtualAttributes()` on
  `TranslationInterface` (was `saveTranslations()`, `deleteTranslations()` and
  `updateOldTranslationAttributes()`); the last one resets every virtual attribute, not only the translated
  ones
- Replaced `Db\I18nActiveQuery::replaceI18nAttributes()` with `withTranslations(array|string|null $languages = null)`,
  which eager loads the translation records of the given languages (every configured language by default).
  A query that returns more than one row applies it on its own, so a list never queries once per record in a
  language it is read in later; `withoutTranslations()` opts out and a single record stays lazy. `I18nActiveQuery` gained
  `joinTranslation()`, rewrites a translated attribute name in `orderBy()` to the
  expression it is stored as, and selects `getColumnAttributes()` rather than `attributes()`. A model that
  stores translations must return an `I18nActiveQuery` from `find()`
- `I18nAttributesTrait::getI18nAttributeName()` and `getI18nAttribute()` (and their `I18nActiveQuery`
  counterpart) gained a third `bool $fallback = false` parameter: with it, a translated attribute that holds
  no value resolves to the untranslated attribute instead
- `Validators\UniqueValidator` now validates a translated target attribute against the joined translation
  value; per-language unique rules expanded by `getI18nRules()` use it instead of the framework validator,
  and `isUniqueRule()` accepts `'unique'`, `yii\validators\UniqueValidator::class` and the skeleton class
- `I18nAttributesTrait::getI18nAttributeNames()` and `Models\Interfaces\I18nAttributeInterface` take
  `?array $languages` (was typed `?string` while being iterated as an array)
- Added `Db\Traits\MigrationTrait::moveI18nColumnsToTranslations()`,
  `restoreI18nColumnsFromTranslations()`, `dropIndexesContainingColumn()` and `getQuotedTableName()`
- Removed `Db\Traits\MigrationTrait::dropI18nColumns()` and made `addI18nColumns()` private. Translated attributes
  have no column of their own anymore, so a migration never adds one; the helper only exists to rebuild the columns
  in `restoreI18nColumnsFromTranslations()`. Its `$allowNull` and `$except` parameters are gone with it
- The historical migrations no longer create, index or alter `_xx` columns, so a fresh install builds the
  source-language schema directly instead of adding the per-language columns just to have the translation migrations
  drop them again. This also unblocks a fresh install: `Media\Migrations\M200117122241File` resolved the current
  model's I18N attributes, which since custom attributes throws while `file` has no `custom_attributes` column yet
- Removed the `enableI18nTables` feature and the `Modules\ModuleTrait` that carried it (the
  `$enableI18nTables` / `$tablePrefix` properties and the `getTableName()`, `getLanguages()` and
  `getI18nClassName()` methods). Modules no longer switch to per-language database tables; models now
  declare a plain `tableName()`. The per-column translation feature (`I18nAttributesTrait`,
  `Db\I18nActiveQuery`, `I18n\I18N`) is unaffected
- Fixed the `Db\ActiveQuery::getModelInstance()` annotation, which declared `@return ActiveRecord<T>`. `ActiveRecord`
  is not generic, so PHPStan resolved that to `ActiveRecord&iterable<T>` and hid every method the concrete model
  adds; it is `@return T` now
- `NestedTreeTrait::getBranchCount()` no longer throws on a record that is not in the tree yet. It divided
  `rgt - lft - 1` by two, which returns a float for an unsaved record whose `lft` / `rgt` are still `null`,
  and that violated the declared `int` return type. It now returns `0` for those records
- `NestedTreeTrait::getFirstAncestor()` now returns `null` (was `false`) when the record has no ancestor,
  matching its `?static` return type
- `I18nAttributesTrait` is no longer generic: the unused `@template T` / `@property class-string<T> $modelClass`
  metadata was removed, so `use` sites no longer need an `@use I18nAttributesTrait<…>` binding
- `Submenu` now hides itself when it has fewer than two visible items; toggle via the new
  `hideSingleItem` option (defaults to `true`)
- `NestedTreeTrait` now maintains a `depth` column (0 for roots, incremented per level), kept in sync on
  insert, on `parent_id` moves (the whole branch is shifted) and by `rebuildNestedTree()`. Models using the
  trait require the new `depth` column — see the per-package migration (e.g. yii2-cms `M260907100000Depth`)
- Sortable reordering now issues its request through `htmx.ajax` (was a raw `fetch`) so the admin
  controllers' out-of-band flash messages are rendered after a drag; the reordered row's primary key is
  now taken from the last id segment, fixing reordering of models whose name contains a dash (e.g.
  `hotspot-asset`)
- `NestedTreeTrait::rebuildNestedTree()` now returns the number of rows it changed (was `void`) and skips
  rows whose `lft`/`rgt` are unchanged; `ReorderActiveRecords::reorderActiveRecordsInternal()` likewise
  returns the updated-row count, so nested-tree reorders (e.g. categories) report whether anything
  changed and run their `afterReorder()` trail
- Removed `UrlManager::$i18nSubdomain` and `UrlManager::hasI18nUrls()`; use `UrlManager::$i18nUrl`
- Renamed `UrlManager::setApplicationLanguage()` to `UrlManager::setLanguage()`, the single point where the
  application language is resolved on every request — override it to customize language detection
- Changed `UrlManager::$defaultLanguage` to `?string` (dropped the `false` type) and to no longer default to
  `sourceLanguage`; when `null` (the default) the language is detected from the browser via
  `Request::getPreferredLanguage()`. Set it explicitly to force a language or, with `$i18nUrl`, to define the
  prefix-less default (otherwise every language gets a path prefix)
- Removed `Behaviors\UserLanguageBehavior`, `Request::getLanguage()` and `Request::getLanguageFromCookie()`
  along with the `?language=` param, the language cookie and the admin user-language override; `UrlManager`
  now resolves the language from `$i18nUrl`, `$defaultLanguage`, or the browser's `Accept-Language` header
  (`Request::getPreferredLanguage()`)
- Added `TrailModelInterface` and `TrailModelTrait` to better handle static analysis of trail models
- Added `GridSearch` and moved properties `search`, `searchParamName`, `searchInputOptions` and `searchUrl`
- Added `GridSummary` methods `getSearchInput`, and `getSearchKeywords` to the new `GridView::$search` property
- Changed `GridView::isSortedByPosition()` to `GridView::isSortable()`
- Refactored `TrailBehavior::formatTrailAttributeValue` to `TrailModelCollection::formatAttributeValue`
- Removed `Html::buttonList()`, use `Html::buttons()` instead
- Removed `GridView::getUpdateButton()`
- Removed `GridView::getDeleteButton()`
- Removed `GridView::getSortableButton()`
- Removed `GridView::renderSelectionForm()`
- Renamed `GoogleAuthenticator` classes to `TwoFactorAuthenticator` classes
- Removed `yii2-timeago`, use `RelativeTime` tag instead
- Replaced `Picture` with `Media`
- Changed materialized tree `path` to a JSON `array` column; `MaterializedTreeTrait::$path` and
  `getAncestorIds()` now work with `int[]` arrays and `findDescendants()` matches via `JSON_CONTAINS`
- Removed `MaterializedTreeTrait::getIdsFromPath()` and `getPathFromIds()` (the comma-string helpers)
- Removed `ArrayHelper::cacheStringToArray()` and `ArrayHelper::createCacheString()`; use native JSON
  `array` columns instead

## 2.6.8 (May 21, 2026)

- Replaced `TrimValidator` with UTF-8 compatible validator using `mb_trim` (available through Symfony polyfill)

## 2.6.7 (Feb 10, 2026)

- Fixed admin redirects to the correct domain when using i18N paths

## 2.6.6 (Jan 29, 2026)

- Fixed `Application::$basePath` for symlinked applications

## 2.6.5 (Jan 26, 2026)

- PHP 8.5 compatibility fixes
- Upgraded 2FA library to version 3

## 2.6.4 (Dec 5, 2026)

- Disabled `PageCache` for draft requests

## 2.6.3 (Nov 5, 2025)

- Added `HexColorInputWidget` and enhanced display for optional color fields
- Removed unused DatePicker language JavaScript include

## 2.6.2 (Oct 20, 2025)

- Fixed Russian flag

## 2.6.1 (Oct 20, 2025)

- Added Russian language support

## 2.6.0 (Oct 2, 2025)

- Added backup and restore methods to `Connection` (Issue #46)
- Added `Connection::$backupOnMigration` option, defaults to `true`
- Added `Dsn` helper class to parse DSN strings
- Changed `FileHelper::generateRandomFilename()` signature
- Locked database schema to MySql

## 2.5.8 (Sep 23, 2025)

- Changed visibility of `UrlManager::replaceSubdomain()` to protected
- Enhanced `SitemapController` to redirect draft domains to production domains (#47)
- Fixed `Url::draft()` helper method to correctly handle strings

## 2.5.7 (Aug 6, 2025)

- Enhanced `TrailModelCollection` display of deleted active records
- Fixed `UrlManager::$i18nSubdomain` for default subdomain
- Fixed admin language URls for `UrlManager::$i18nSubdomain`

## 2.5.6 (Jul 15, 2025)

- Added `position` array key to `UrlManager` rules definition to allow for custom position of rules
- Enhanced `Controller::errorOrSuccess()` to also accept arrays and strings
- Fixed error for array values in `TrailGridView::renderUpdatedAttributeValues()`

## 2.5.5 (Jul 1, 2025)

- Fixed `ActiveRecordErrorLogger` default message with composite primary keys

## 2.5.4 (Jun 27, 2025)

- Annotation changes for static analysis (Yii 2.0.53)

## 2.5.3 (Jun 24, 2025)

- Updated country names for all supported languages

## 2.5.2 (Jun 12, 2025)

- Fixed `AjaxRouteTrait`

## 2.5.1 (Jun 5, 2025)

- Enhanced `StreamUploadedFile` to also set file name from URL on error
- Fixed `View::prepareJsArguments` to properly handle non-arrays
- Fixed default `UrlManager::$languages` initialization

## 2.5.0 (May 25, 2025)

- Requires PHP 8.3+
- Added wildcard support for `UrlManager::$redirectMap`
- Added `View::registerJsModules()` to register JS modules
- Added `declare(strict_types=1);` to migration template (Issue #43)
- Added `Request::isDraftRequest()` to check if the current request is a draft request
- Moved `Request::$draftSubdomain` and `Request::getDraftHostInfo()` to `UrlManager` (Issue #42)
- Removed `Request::getProductionHostInfo()`
- Updated Codeception

## 2.4.10 (May 21, 2025)

- Added `Request::setIsDraft()` for custom draft detectionÏ

## 2.4.9 (May 5, 2025)

- Enhanced default `I18N::getLanguages()` initialization, to prevent a temporary language setting to lock in the
  available languages before the `I18N` component was configured

## 2.4.8 (May 5, 2025)

- Added error message in `EmailController::actionTest` when email is not set (Issue #36)
- Add flash success messages for adding or revoking users' RBAC (Issue #33)
- Extracted `Controller::$spacelessOutput` to `Controller::stripWhitespaceFromHtml()` method
- Fixed loading default values for `User` creation via console (Issue #35)

## 2.4.7 (Mar 24, 2025)

- Added auto-generated color fields for `HexColorValidator`
- Enhanced `TypeGridViewTrait::$typeDefaultItem` to also accept `false`
- Enhanced `TypeGridViewTrait::typeDropdownItems()` to use type as index

## 2.4.6 (Mar 20, 2025)

- Added `SensitiveAttributeValidator` for auto-generated password fields

## 2.4.5 (Mar 20, 2025)

- Added `EmailController` command to send test emails

## 2.4.4 (Jan 26, 2025)

- Fixed `maintenance.php` stub file

## 2.4.3 (Jan 23, 2025)

- Enhanced 2FA secret generation
- Updated composer dependencies

## 2.4.2 (Dec 17, 2024)

- Changed `Skipping unsafe attribute` log level to `debug`
- Fixed `RelationValidator` to also cast `null` relations on new records

## 2.4.1 (Dec 12, 2024)

- Added `Yii2::setDraftHttpHost()` and `Yii2::setProductionHttpHost()` for codeception tests
- Changed `AssetDirectoryTrait::$assetPath` default value to `@runtime/tests/assets`
- Fixed `TrailGridView::renderTrailActiveRecordAttribute()` for empty relations

## 2.4.0 (Nov 29, 2024)

- Changed signature of `GridView::getUpdateButton`, `GridView::getDeleteButton`, `GridView::getSortableButton` and `
  GridView::getSelectionButton() to accept an array of options
- Changed unsafe field warning in `ActiveFormTrait` to debug level
- Enforced strict types
- Fixed empty name and email sanitization in `AuthClientSignupForm`
- Fixed `User` nullable IP address
- Fixed `MaterializedTreeTrait` with an empty path

## 2.3.20 (Nov 28, 2024)

- Added `ModuleTrait::getLanguages()`
- Added `MigrationTrait::getForeignKeyName()`
- Added `StatusGridViewTrait::$statusIsActive` to manually set the status dropdown state
- Updated `StatusGridViewTrait::$statusDefaultItem` to allow `false` and `StatusGridViewTrait::$statusParamName` to null

## 2.3.19 (Nov 26, 2024)

- Fixed `UserActiveForm` button label
- Fixed `IconFilenameAttributeTrait` range validator

## 2.3.18 (Oct 22. 2024)

- Added `DynamicRangeValidator` type detection to `AttributeTypecastBehavior`
- Enhanced default mailer DSN settings (Issue #32)
- Enhanced `LogGridView` by HTML encoding the message column (Issue #31)
- Fixed `TrailModelCollection` to allow primary keys with "-" characters
- Fixed `User` dynamic range validation for language and timezone attributes
- Fixed a bug in `AccountResendConfirmForm` where the `User::$updated_at` was not updated on sending the confirmation
  email

## 2.3.17 (Oct 2, 2024)

- Added `.text-invalid` CSS class
- Improved `RedirectBehavior` to be disabled for console if `UrlManager` was not properly configured

## 2.3.16 (Oct 1, 2024)

- Added optional parameter `prepend` to `ApplicationTrait::addUrlManagerRules()` to prepend the rules to the beginning
  of the `UrlManager` rules array

## 2.3.15 (Sep 17, 2024)

- Added the option to remove empty span `tags` from HtmlValidator output, defaults to false
- Enhanced `MigrationController` to not offer to create a new user if an error occured during migration
- Fixed translations in `AuthItemGridView` for message sources with `forceTranslation` set to `true`

## 2.3.14 (Sep 11, 2024)

- Fixed an issue with `TinyMceEditor` not whitelisting combined CSS classes

## 2.3.13 (Sep 6, 2024)

- Added `UserController::actionCreate()` console command to create a new user, this will now be called after migrations
  if no other user exists
- Added `_POST.SignupForm.password` to `Log::$maskVars`
- Enhanced `SessionTrait::$cookieDomain` to use the default cookie domain from the container definition rater than from
  the application parameters
- Removed the user count check from `Hirtz\Skeleton\Web::isSignupEnabled()`

## 2.3.12 (Aug 23, 2024)

- Enhanced `HtmlValidator::$allowedClasses` to allow multiple CSS classes per element
- Enhanced Bootbox confirms making the first button focused by default (Issue #29)
- Fixed upload progress bar z-index (Issue #25)

## 2.3.11 (Aug 19, 2024)

- Added `ApplicationTrait::addUrlManagerRules()` to prevent the initialization of the URL manager before the bootstrap
  is completed

## 2.3.10 (Aug 19, 2024)

- Added `AdminButton::$adminLinkZIndex` (still defaults to '3')
- Added `Hirtz\Skeleton\Models\Interfaces\SitemapInterface` (Issue #30)
- Changed `ActiveDataProvider::prepareQuery()` visibility to `protected`
- Fixed `GridView::$searchUrl` to reset current page on default

## 2.3.9 (Jul 26, 2024)

- Fixed `AuthController::getAuthItem()` return type

## 2.3.8 (Jul 23, 2024)

- Fixed `Module::$defaultRoute` to correctly set the default route for the admin module

## 2.3.7 (Jul 16, 2024)

- Fixed `Hirtz\Skeleton\Widgets\AdminButton` CSS width and height attributes

## 2.3.6 (Jul 8, 2024)

- Added `View::POS_MODULE` constant to handle module scripts and JavaScript files
- Added support for JS and CSS files in `AjaxRouteTrait`
- Changed `Hirtz\Skeleton\Modules\Admin\Module::$name` to `Module::getName()` to prevent translation issues
  (Issue #28)

## 2.3.5 (Jun 28, 2024)

- Fixed `AttributeTypecastBehavior` to cast integer "0" values to `0` not `null`

## 2.3.4 (Jun 24, 2024)

- Fixed MySQL JSON columns bug via migration. This normalizes JSON columns for MariaDB and MySQL with the introduction
  of JSON support in Yii 2.0.49.
-

## 2.3.3 (Apr 24, 2024)

- Extracted new `MessageController` actions to their own package `davidhirtz/yii2-translation`

## 2.3.2 (Apr 24, 2024)

- Added `MessageController` extending the default framework controller by two methods `export-csv` and `export-csv`
- Added `MigrationTrait::dropColumnIfExists()` and `MigrationTrait:dropIndexIfExists()`
- Enhanced `ErrorAction` to allow for custom error messages for 404 and 403 errors
- Enhanced `NavBar::$languageRoute` to be merged with query parameters
- Enhanced `UrlManager` to accept a `defaultLanguage` for each URL created
- Fixed `UrlManager::setApplicationLanguage()` to keep the query parameters when redirecting default language URLs
- Fixed `UrlManager::getImmutableRuleParams()` to include allowed URL characters

## 2.3.1 (Apr 15, 2024)

- Extracted draft domain creating from `Request` to `Url::draft()`
- Moved host info configuration to `Hirtz\Skeleton\Codeception\Yii2` for testing

## 2.3.0 (Apr 5, 2024)

- Added `DashboardController::$roles` to allow adjusting the admin dashboard roles, defaults to the roles used in the
  dashboard panels
- Fixed `UrlMananger::$languages` to accept `false` to disable the application language being set via URLs
- Removed public properties `$roles`, `$navbarItems` and `$panels` from `Hirtz\Skeleton\Modules\Admin\Module`
  in favor of the new methods to add navbar items and dashboard panels as described in new `ModuleInterface`.

## 2.2.7 (Apr 3, 2024)

- Enhanced `ActiveFormTrait::plainTextRow()` to accept more options
- Renamed `StatusFieldTrait::getStatuses()` to `getStatusItems()`
- Renamed `StatusGridViewTrait::$defaultStatusItem` to `StatusGridViewTrait::$statusDefaultItem`
- Renamed `TypeFieldTrait::getTypes()` to `getTypeItems()`
- Renamed `TypeGridViewTrait::$defaultTypeItem` to `TypeGridViewTrait::$typeDefaultItem`
- Upgraded TinyMCE to version 7.0 (CVE-2024-29881)

## 2.2.6 (Mar 11, 2024)

- Changed default navbar order for admin module
- Fixed empty value bug in `ActiveField::hexColor()`

## 2.2.5 (Mar 11, 2024)

- Fixed `color` test

## 2.2.4 (Mar 11, 2024)

- Added mailer transport DSN fallback (Issues #24)
- Added support for `color`  type input fields (Issue #22)
- Enhanced `Nav` to allow for `active` callables
- Enhanced `Navbar` item sorting by introducing an optional `order` array key
- Enhanced `SignupForm` to better handle the token validation

## 2.2.3 (Mar 4, 2024)

- Fixed an issue in `UserLanguageBehavior` where a previous language cookie would override the user language even when
  logged in
- Fixed autogenerated I18N fields in `ActiveFormTrait`

## 2.2.2 (Mar 2, 2024)

- Added `User::isDeletable()`
- Enhanced `ActiveFormTrait` to better distinguish between `fieldOptions` and `inputOptions`
- Fixed `ActiveFormTrait::$showSubmitButton` to correctly hide the submitting button when set to `false`
- Fixed `user/update.php` view to correctly display the user delete form

## 2.2.1 (Mar 1, 2024)

- Added `AccountResendConfirmActiveForm` and `PasswordResetActiveForm`
- Added additional field types to auto-generated fields in `ActiveFormTrait`
- Added `padding-block: 0` to `.form-control` to fix the padding issues with date inputs
- Changed return type of `StatusGridViewTrait::getStatusIcon()` and `TypeGridViewTrait::getTypeIcon()` to `string`
- Renamed `GridView::$searchFormOptions` to `GridView::$searchInputOptions`

## 2.2.0 (Feb 29, 2024)

- Changed `AuthClientSignupForm` and `Hirtz\Skeleton\Modules\Admin\Models\forms\UserForm` to extend
  from `Model` instead of `User` (Issue #21)
- Extracted user picture upload methods and options to `UserPictureForm`
- Removed `Identity` class and replaced it with `User` class (Issue #20)
- Removed `User::findByEmail()` and `User::findByName()` for corresponding `UserQuery` methods
- Renamed `UserForm` to `AccountUpdateForm`, it now extends `Model` instead of `User`

## 2.1.23 (Feb 1, 2024)

- Added `Controller::errorOrSuccess()`
- Changed `DuplicateActiveRecord` to always return the new model
- Fixed `StreamUploadedFile` to correctly handle local files
- Fixed `UniqueValidator` (Issue #18)

## 2.1.22 (Feb 1, 2024)

- Added `Module::EVENT_INIT` for better module manipulation from extensions

## 2.1.21 (Feb 1, 2024)

- Added `CreateValidatorsEvent` to simplify the creation of validators from behaviors

## 2.1.20 (Jan 26, 2024)

- Changed the signature of `I18nAttributesTrait::isUniqueRule()` to accept any argument

## 2.1.19 (Jan 26, 2024)

- Fixed `I18nAttributesTrait::getI18nRules()` to correctly set the `targetAttributes` of translated attributes

## 2.1.18 (Jan 25, 2024)

- Fixed `TinyMceEditor` table button
- Fixed `Trail::renderCreatedAttributeValue()` (Issue #17)

## 2.1.17 (Jan 24, 2024)

- Added `AttributeTypecastBehavior::$castBooleansAsInt` to allow casting booleans as integers
- Fixed `UserLogin::getDisplayIp()`
- Fixed `RedirectBehavior` to also set the previous URL when on insert
- Moved asset manager setup in tests from `Yii2` module to `AssetDirectoryTrait` and `BaseCest`

## 2.1.16 (Jan 13, 2024)

- Added `'data-method'=>'add'` (Issue #15)
- Added `UserDeletePanel`
- Fixed `UrlManager` to only apply the default language when the I18N component is configured with more than one
  language

## 2.1.16 (Jan 13, 2024)

- Added `'data-method'=>'add'` (Issue #15)
- Added `UserDeletePanel`
- Fixed `UrlManager` to only apply the default language when the I18N component is configured with more than one
  language

## 2.1.15 (Jan 12, 2024)

- Removed `View::registerTwitterCardMetaTags()`
- Removed `View::registerStructuredDataBreadcrumbs()` and `View::registerStructuredData()` in favor
  of `Hirtz\Skeleton\Helpers\StructuredData`

## 2.1.14 (Jan 12, 2024)

- Extracted `ActiveRecord::getTraitAttributeLabels()`, `ActiveRecord::getTraitRules()`
  and `ActiveRecord::getTraitNames()` to `ModelTrait`

## 2.1.13 (Jan 12, 2024)

- Added `UserLanguageBehavior` to better control when the user language should be set and updated
- Removed `ActiveRecord::typecastAttributes()` in favor
  of `Hirtz\Skeleton\Behaviors\AttributeTypecastBehavior`
- Removed `UserQuery::selectIdentityAttribute`

## 2.1.12 (Jan 9, 2024)

- Removed `ErrorController` introduced in 2.1.11 in favor of `Hirtz\Skeleton\Web\ErrorAction`

## 2.1.11 (Jan 9, 2024)

- Added `ErrorController`

## 2.1.10 (Jan 9, 2024)

- Fixed Rector (Issue #14)
- Fixed TinyMCE CSS (Issue #7)

## 2.1.9 (Jan 8, 2024)

- Fixed `GridView` PHPDoc block
- Updated dependencies

## 2.1.8 (Jan 8, 2024)

- Added PHPDoc template to `GridView`

## 2.1.7 (Jan 8, 2024)

- Added `LinkDataColumn`
- Changed `CounterColumn::$countHtmlOptions` to `CounterColumn::$wrapperOptions` to be consistent with `LinkDataColumn`

## 2.1.6 (Dec 29, 2023)

- Enhanced Yii2 codeception test module to create and destroy the assets folder for each test
- Removed `BaseCest` in favor of `UserFixtureTrait` trait

## 2.1.5 (Dec 26, 2023)

- Enhanced test suite and simplified test configuration

## 2.1.4 (Dec 19, 2023)

- Enhanced `GridView` annotations for static analysis

## 2.1.3 (Dec 19, 2023)

- Enhanced `DuplicateActiveRecord` to work with generic template
- Fixed `NestedTreeTrait::isTransactional()`

## 2.1.2 (Dec 19, 2023)

- Changed `Yii::createObject()` calls with arrays back to `Yii::$container->get()` for better IDE support

## 2.1.1 (Dec 18, 2023)

- Minor PHPDoc updates for static analysis of `yii2-media` and `yii2-cms` packages

## 2.1.0 (Dec 18, 2023)

- Added Codeception test suite
- Added GitHub Actions CI workflow
- Added `I18nActiveQuery` and extracted `ActiveRecord::$i18nAttributes` to `I18nAttributesTrait`

## 2.0.14 (Dec 8, 2023)

- Extracted shared model methods from `ActiveRecord` to `ModelTrait`
- Removed `ActiveRecord::logErrors` and replaced it with `\Hirtz\Skeleton\Log\ActiveRecordErrorLogger::log()`
- Reverted `ActiveRecord::isAttributeChanged()` to also accept arrays of attributes,
  added `ActiveRecord::hasChangedAttributes()` instead

## 2.0.13 (Nov 15, 2023)

- Extended `ActiveRecord::isAttributeChanged()` to also accept arrays of attributes
- Fixed a bug in `Sitemap` URL generation

## 2.0.12 (Nov 10, 2023)

- Fixed a bug in `ActiveFormTrait` where the attribute name would be set for existing field methods

## 2.0.11 (Nov 9, 2023)

- Fixed automatic links from breadcrumbs

## 2.0.10 (Nov 9, 2023)

- Enhanced `IconFilenameAttributeTrait::getIconFilenames()` to also accept aliases

## 2.0.9 (Nov 9, 2023)

- Added `ActiveRecord::getTraitRules()` and `ActiveRecord::getTraitAttributeLabels()`
- Added `Hirtz\Skeleton\Models\Traits\IconFilenameAttributeTrait`
- Added `StringHelper::humanizeFilename()`
- Added `Html::truncateText()`
- Enhanced `Hirtz\Skeleton\Widgets\Forms\ActiveFormTrait`
- Improved `AccountActiveForm::oldPasswordField()`
- Removed `@config` alias as it is interfering with `yii2-config` module

## 2.0.8 (Nov 8, 2023)

- Added `ViewContextInterface` to `Widget` for better view context handling

## 2.0.7 (Nov 7, 2023)

- Changed the default view path to `@views` alias
- Fixed bug in `Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\UserFormTrait`

## 2.0.6 (Nov 7, 2023)

- Updated `modules/admin/views/dashboard/error.php` view

## 2.0.5 (Nov 7, 2023)

- Improved `\Hirtz\Skeleton\Modules\Admin\Widgets\Forms\Traits\TypeFieldTrait`
- Removed unbound version constraints in third-party composer packages
- Removed unnecessary composer requirements from Yii2 via "provide"

## 2.0.4 (Nov 7, 2023)

- Added `Hirtz\Skeleton\Widgets\AdminButton`
- Added default aliases for `@app`, `@config`, `@messages`, `@resources`, `@root` and `@views`
- Moved `\Hirtz\Skeleton\Core\ApplicationTrait` to `Hirtz\Skeleton\Base\Traits\ApplicationTrait`
- Improved error view
- Removed unused `BootstrapPluginAsset`
- Removed unused `CountryDropdown` and `LanguageDropdown` widgets
- Removed unused `User::hasAuthClient()`
- Removed `Toolbar`
- Removed `ErrorAction`

## 2.0.2 (Nov 6, 2023)

- Added `Widget` abstract class, for minimal widget implementations
- Added action `DuplicateActiveRecord` and a related `DuplicateActiveRecordEvent` event
- Change `m151125_140002_init` migration name to be consistent with all other migrations (non-breaking change)
- Moved `I18nAttributesTrait`, `MaterializedTreeTrait`, `NestedTreeTrait`, `StatusAttributeTrait`
  and `TypeAttributeTrait` to namespace `Hirtz\Skeleton\Models\Traits`
- Moved `MigrationTrait` to namespace `Hirtz\Skeleton\Db\Traits`
- Moved `OwnerRule` to namespace `Hirtz\Skeleton\Rbac\Rules`, migration `M231105142331OwnerRule` changes the
  namespace of the rule in the database
- Removed `ActiveRecord::updatePosition()`, use `\Hirtz\Skeleton\Db\Actions\ReorderActiveRecords`
  instead

## 2.0.1 (Nov 4, 2023)

- Extracted AJAX Route functionality from controller to `Hirtz\Skeleton\Controllers\Traits\AjaxRouteTrait`
- Removed default route from application and restored default value of `UrlManager::$enableStrictParsing`
- Renamed `View::getDescription()` to `View::getMetaDescription()`
- Renamed `View::getTitle()` to `View::getDocumentTitle()`

## 2.0.0 (Nov 3, 2023)

- Added `IdentityActiveForm` for a better separation of admin and user forms
- Changed namespaces from `Hirtz\Skeleton\Admin\widgets\grid`
  to `Hirtz\Skeleton\Admin\widgets\grids` and `Hirtz\Skeleton\Admin\widgets\nav`
  to `Hirtz\Skeleton\Admin\widgets\navs`
- Changed namespaces for `CounterColumn` to `Hirtz\Skeleton\Admin\widgets\grids\columns`
- Changed namespaces for `MessageSourceTrait`, `StatusGridViewTrait` and `TypeGridViewTrait`
  to `Hirtz\Skeleton\Admin\widgets\grids\Traits`
- Enhanced `TrailGridView` now tries to automatically load related models
- Moved source code to `src` folder
- Moved all models, data providers and widgets out of `base` folder, to override them use Yii's dependency injection
  container
- Removed `Redirect::getActiveForm()`, to override the active form use Yii's dependency injection
  container

## 1.9.3  (Nov 3, 2023)

- Fixed TinyMCE relative URLs and table resizing (Issue #4)
- Removed double active hidden fields for I18n attributes in `ActiveForm::getAutogeneratedField()` (Issue #3)

## 1.9.2  (Nov 3, 2023)

- Fixed `HtmlValidator` adding `<br>` tags after `<tr>` tags

## 1.9.1 (Oct 30, 2023)

- Replaced CKEditor with TinyMCE 6, because CKEditor reached its End of Life (EOL) in June 2023