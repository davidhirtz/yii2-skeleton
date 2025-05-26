## 2.5.1 (in development)

- Enhanced `StreamUploadedFile` to also set file name from URL on error
- Fixed `View::prepareJsArguments` to properly handle non-arrays

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
- Enhanced `MigrationController` to not offer to create a new user if an error occurred during migration
- Fixed translations in `AuthItemGridView` for message sources with `forceTranslation` set to `true`

## 2.3.14 (Sep 11, 2024)

- Fixed an issue with `TinyMceEditor` not whitelisting combined CSS classes

## 2.3.13 (Sep 6, 2024)

- Added `UserController::actionCreate()` console command to create a new user, this will now be called after migrations
  if no other user exists
- Added `_POST.SignupForm.password` to `Log::$maskVars`
- Enhanced `SessionTrait::$cookieDomain` to use the default cookie domain from the container definition rater than from
  the application parameters
- Removed the user count check from `davidhirtz\yii2\skeleton\web::isSignupEnabled()`

## 2.3.12 (Aug 23, 2024)

- Enhanced `HtmlValidator::$allowedClasses` to allow multiple CSS classes per element
- Enhanced Bootbox confirms making the first button focused by default (Issue #29)
- Fixed upload progress bar z-index (Issue #25)

## 2.3.11 (Aug 19, 2024)

- Added `ApplicationTrait::addUrlManagerRules()` to prevent the initialization of the URL manager before the bootstrap
  is completed

## 2.3.10 (Aug 19, 2024)

- Added `AdminButton::$adminLinkZIndex` (still defaults to '3')
- Added `davidhirtz\yii2\skeleton\models\interfaces\SitemapInterface` (Issue #30)
- Changed `ActiveDataProvider::prepareQuery()` visibility to `protected`
- Fixed `GridView::$searchUrl` to reset current page on default

## 2.3.9 (Jul 26, 2024)

- Fixed `AuthController::getAuthItem()` return type

## 2.3.8 (Jul 23, 2024)

- Fixed `Module::$defaultRoute` to correctly set the default route for the admin module

## 2.3.7 (Jul 16, 2024)

- Fixed `davidhirtz\yii2\skeleton\widgets\AdminButton` CSS width and height attributes

## 2.3.6 (Jul 8, 2024)

- Added `View::POS_MODULE` constant to handle module scripts and JavaScript files
- Added support for JS and CSS files in `AjaxRouteTrait`
- Changed `davidhirtz\yii2\skeleton\modules\admin\Module::$name` to `Module::getName()` to prevent translation issues
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
- Moved host info configuration to `davidhirtz\yii2\skeleton\codeception\Yii2` for testing

## 2.3.0 (Apr 5, 2024)

- Added `DashboardController::$roles` to allow adjusting the admin dashboard roles, defaults to the roles used in the
  dashboard panels
- Fixed `UrlMananger::$languages` to accept `false` to disable the application language being set via URLs
- Removed public properties `$roles`, `$navbarItems` and `$panels` from `davidhirtz\yii2\skeleton\modules\admin\Module`
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

- Changed `AuthClientSignupForm` and `davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm` to extend
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
  of `davidhirtz\yii2\skeleton\helpers\StructuredData`

## 2.1.14 (Jan 12, 2024)

- Extracted `ActiveRecord::getTraitAttributeLabels()`, `ActiveRecord::getTraitRules()`
  and `ActiveRecord::getTraitNames()` to `ModelTrait`

## 2.1.13 (Jan 12, 2024)

- Added `UserLanguageBehavior` to better control when the user language should be set and updated
- Removed `ActiveRecord::typecastAttributes()` in favor
  of `davidhirtz\yii2\skeleton\behaviors\AttributeTypecastBehavior`
- Removed `UserQuery::selectIdentityAttribute`

## 2.1.12 (Jan 9, 2024)

- Removed `ErrorController` introduced in 2.1.11 in favor of `davidhirtz\yii2\skeleton\web\ErrorAction`

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
- Removed `ActiveRecord::logErrors` and replaced it with `\davidhirtz\yii2\skeleton\log\ActiveRecordErrorLogger::log()`
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
- Added `davidhirtz\yii2\skeleton\models\traits\IconFilenameAttributeTrait`
- Added `StringHelper::humanizeFilename()`
- Added `Html::truncateText()`
- Enhanced `davidhirtz\yii2\skeleton\widgets\forms\ActiveFormTrait`
- Improved `AccountActiveForm::oldPasswordField()`
- Removed `@config` alias as it is interfering with `yii2-config` module

## 2.0.8 (Nov 8, 2023)

- Added `ViewContextInterface` to `Widget` for better view context handling

## 2.0.7 (Nov 7, 2023)

- Changed the default view path to `@views` alias
- Fixed bug in `davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\UserFormTrait`

## 2.0.6 (Nov 7, 2023)

- Updated `modules/admin/views/dashboard/error.php` view

## 2.0.5 (Nov 7, 2023)

- Improved `\davidhirtz\yii2\skeleton\modules\admin\widgets\forms\traits\TypeFieldTrait`
- Removed unbound version constraints in third-party composer packages
- Removed unnecessary composer requirements from Yii2 via "provide"

## 2.0.4 (Nov 7, 2023)

- Added `davidhirtz\yii2\skeleton\widgets\AdminButton`
- Added default aliases for `@app`, `@config`, `@messages`, `@resources`, `@root` and `@views`
- Moved `\davidhirtz\yii2\skeleton\core\ApplicationTrait` to `davidhirtz\yii2\skeleton\base\traits\ApplicationTrait`
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
  and `TypeAttributeTrait` to namespace `davidhirtz\yii2\skeleton\models\traits`
- Moved `MigrationTrait` to namespace `davidhirtz\yii2\skeleton\db\traits`
- Moved `OwnerRule` to namespace `davidhirtz\yii2\skeleton\rbac\rules`, migration `M231105142331OwnerRule` changes the
  namespace of the rule in the database
- Removed `ActiveRecord::updatePosition()`, use `\davidhirtz\yii2\skeleton\db\actions\ReorderActiveRecords`
  instead

## 2.0.1 (Nov 4, 2023)

- Extracted AJAX Route functionality from controller to `davidhirtz\yii2\skeleton\controllers\traits\AjaxRouteTrait`
- Removed default route from application and restored default value of `UrlManager::$enableStrictParsing`
- Renamed `View::getDescription()` to `View::getMetaDescription()`
- Renamed `View::getTitle()` to `View::getDocumentTitle()`

## 2.0.0 (Nov 3, 2023)

- Added `IdentityActiveForm` for a better separation of admin and user forms
- Changed namespaces from `davidhirtz\yii2\skeleton\admin\widgets\grid`
  to `davidhirtz\yii2\skeleton\admin\widgets\grids` and `davidhirtz\yii2\skeleton\admin\widgets\nav`
  to `davidhirtz\yii2\skeleton\admin\widgets\navs`
- Changed namespaces for `CounterColumn` to `davidhirtz\yii2\skeleton\admin\widgets\grids\columns`
- Changed namespaces for `MessageSourceTrait`, `StatusGridViewTrait` and `TypeGridViewTrait`
  to `davidhirtz\yii2\skeleton\admin\widgets\grids\traits`
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