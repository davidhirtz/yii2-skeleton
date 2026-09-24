## Unreleased

- Added `Mail\Mailer::getTransportError()`: the system page and `email/test` name a missing mailer bridge or HTTP client
- Fixed an error response to an admin request emptying the page behind its modal
- Removed `FileHelper::getExtensionFromUrl`
- Replaced `FileHelper::createConfigFile()` with `Helpers\ConfigFile::write()`, which sorts the keys

## 3.2.0 (September 24, 2026)

- Replaced `yiisoft/yii2-symfonymailer` with `symfony/mailer` directly:
  `mailerDsn` resolves against every installed Symfony mailer bridge (`resend+api://` with `symfony/resend-mailer`);
  signing, encryption and the header helpers are gone (use `Message::$email`)
- Changed `Mail\Mailer` to log a transport failure and answer `false` instead of throwing an exception

## 3.1.1 (September 24, 2026)

- Added `Db\Commands\RenumberPositions`, which renumbers each parent's rows `1..n` in one statement
- Changed `AdminModelTrait::getAdminPositionLabel()` to take the parent's count as `$total` instead of querying the siblings

## 3.1.0 (September 24, 2026)

- Fixed `TranslationTrait::setAttribute()` on a record whose translations were not loaded yet
- Fixed `Web\Application` deriving its base path from a requested static file
- Removed `Helpers\Image` and the `yiisoft/yii2-imagine` and `ext-imagick` requirements; image processing now lives in `yii2-media`
- Changed `VisibleAttributeInterface::getVisibleAttribute()` to answer `null` instead of `false` and to take a `$language`

## 3.0.0 (September 23, 2026)

- Renamed the namespace `davidhirtz\yii2\skeleton\` to `Hirtz\Skeleton\` and every directory from lowercase to StudlyCase
  (`models\forms\LoginForm` is `Models\Forms\LoginForm`, `modules\admin\controllers\` is `Modules\Admin\Controllers\`)
- Moved the views out of `src/`: `resources/views/`, `resources/views/admin/` (was `modules/admin/views/`) and `resources/mail/`
- Replaced English-text message keys with `UPPER_SNAKE_CASE`, domain-first keys resolved with `forceTranslation`
  (`ACCOUNT_BACK_TO_LOGIN`); renamed the `countries` category to `country`; dropped `ru`, `zh-CN` and `zh-TW`
- Changed `Web\Application::$controllerNamespace` to `App\Controllers` and `Console\Application::$controllerNamespace` to
  `App\Commands`; a project's migrations are `App\Migrations`; removed the `@App` alias (`@app` names the same directory)
- Required `ext-intl`; added `sentry/sentry`; removed `yiisoft/yii2-authclient`, `yiisoft/yii2-bootstrap4`,
  `davidhirtz/yii2-timeago` and every `npm-asset/*` package; `bower-asset/jquery` is `provide`d and never served
- Replaced the widget layer with `Html\` (one `Stringable` class per element) and `Widgets\` (`Grids`, `Forms`, `Navs`,
  `Panels`, `Buttons`); removed `widgets\bootstrap\*`, `widgets\fontawesome\*`, `widgets\forms\*`, `widgets\jui\*`,
  `helpers\StructuredData`, `Html::buttonList()` and `Html::buttons()`; the admin runs on htmx 4 without jQuery
- Removed social login: `Auth\Clients\*`, `Models\AuthClient`, `Models\Forms\AuthClientSignupForm`, the `authClientCollection`
  component, `User::getAuthClients()`, `LoginForm::$enableFacebookLogin`, `SignupForm::$enableFacebookSignup` and the `auth_client` table
- Removed the `user` columns `picture`, `first_name`, `last_name`, `birthdate`, `city` and `country` with `User::getFullName()`,
  `getCountries()`, `getPictureUrl()` and `Models\Forms\UserPictureForm`; `User` is a `CustomAttributeInterface` and the values move into `user.custom_attributes`
- Renamed `GoogleAuthenticator*` to `TwoFactorAuthenticator*` (`Models\Forms\TwoFactorAuthenticatorForm`, `Validators\TwoFactorAuthenticationValidator`,
  `Web\User::$enableTwoFactorAuthentication`, `params.twoFactorAuthenticationIssuer`); `user.google_2fa_secret` is `two_factor_secret`,
  encrypted and read through `getTwoFactorAuthenticationSecret()` / `hasTwoFactorAuthentication()`; enabling 2FA issues `User::RECOVERY_CODE_COUNT` recovery codes
- Moved tokens to `Models\UserToken` (`user_token`, HMAC-stored, expiring after `User::$tokenLifetime`): `user` loses `verification_token`,
  `password_reset_token` and `google_2fa_recovery_codes` and gains `email_confirmed_at`; `generate*Token()` is `create*Token()`,
  `getEmailConfirmationUrl()` / `getPasswordResetUrl()` are `createEmailConfirmationUrl()` / `createPasswordResetUrl()`; the URLs carry no `email`
- Renamed `user.password_salt` to `password_scheme` (`User::PASSWORD_PEPPER` or `null`); the `passwordPepper` param is appended
  before hashing, `params/pepper` generates it, `isPasswordHashOutdated()` rehashes on login; `$passwordMinLength` is 8, `$passwordMaxLength` 72
- Dropped every v2 password hash on upgrade; `upgrade/passwords` mails the reset links, `user/password <email>` sets one from the console
- Replaced the verb permissions with one per model: `User::AUTH_USER` (`user`) replaces `AUTH_USER_CREATE`, `AUTH_USER_UPDATE` and
  `AUTH_USER_DELETE`, `Redirect::AUTH_REDIRECT` (`redirect`) replaces `AUTH_REDIRECT_CREATE`; `AUTH_USER_ASSIGN` and `Trail::AUTH_TRAIL_INDEX` stay
- Removed `Rbac\Rules\OwnerRule`; `Web\User::canManageUser()` is the policy, also refusing a target holding a permission the
  acting user lacks, and the only `can()` param left is `user`; `auth_rule` is emptied
- Added the `manager` role (`User::AUTH_ROLE_MANAGER`) and flattened `admin`: a role lists permissions, never another role;
  `Modules\Admin\Module::AUTH_SYSTEM` (`system`) guards the error logs, `phpinfo()` and the server facts and is held by `admin` alone
- Stored `auth_item.description` and an order trail's `trail.message` as an `I18n\Message` pointer rendered in the reader's
  language; `Trail::createOrderTrail()` and `MigrationTrait::addPermission()` take one; removed `Widgets\Grids\Traits\MessageSourceTrait`
- Renamed `trail.model` and `translation.model` to `model_class`; `Trail::getModelClass()` / `getDataModelClass()` are
  `getModelRecord()` / `getDataModelRecord()`, `TrailModelCollection::getModelByNameAndId()` is `getModelByClassAndId()`
- Added `Models\Interfaces\AdminModelInterface` + `Models\Traits\AdminModelTrait` (`getAdminRoute()`, `getAdminName()`, `getAdminType()`,
  `getAdminIcon()`, `getAdminParent()`, `getAdminIndexBreadcrumb()`, `getAdminSubtitle()`, `getParamName()`, `getPermissionName()`) and
  `TrailModelInterface` + `TrailModelTrait` extending it; `User::getTrailModelName()` / `getTrailModelType()` are `getAdminName()` / `getAdminType()`
- Changed `getTypes()` and `getStatuses()` to instance methods returning `Models\Types\Type` / `Models\Statuses\Status` objects
  (`Type::make(self::TYPE_X)->name()->icon()->plural()->modelClass()->hiddenFields()->customAttributes()->available()`);
  an array throws, `getTypeOptions()` is gone, and both are configurable in the container through `setTypes()` / `setStatuses()`
- Changed `Type::hiddenFields()` to take attribute names; a hidden attribute is unsafe, unvalidated and unrendered, and its stored
  value survives a save; a model using `VisibleAttributeTrait` must declare `Models\Interfaces\VisibleAttributeInterface`
- Changed `Widgets\Forms\Fields\TypeSelectField` to reload the whole page on every change (`Field::reloadsForm()`,
  `Web\Request::isFormReload()`); an action on such a model must skip the save for that request
- Changed `Models\UserLogin::$type` to an integer column (`TYPE_OTHER` 1, `TYPE_LOGIN` 2, `TYPE_COOKIE` 3, `TYPE_SIGNUP` 4,
  `TYPE_CONFIRM_EMAIL` 5, `TYPE_RESET_PASSWORD` 6) and `Web\User::$loginType` to an `int`; `Trail::TYPE_DEFAULT` is `13`
- Moved `Modules\Admin\Module::$alias` to `params.adminAlias`; removed `$showInBreadcrumbs`, `getDashboardPanels()`, `setDashboardPanels()`,
  `getNavBarItems()` and `setNavBarItems()`; a submodule contributes through `Modules\Admin\ModuleInterface::aside()` and `dashboard()`
- Added `Modules\Admin\Module::$languages`, `$languageSessionKey`, `getSessionLanguage()` and `setSessionLanguage()`; the admin language
  is picked through the POST-only `admin/account/language`; removed `Behaviors\UserLanguageBehavior`, `Request::getLanguage()` and the language cookie
- Changed `Modules\Admin\Module::$trailLifetime` to `int|false`; added `$userLoginLifetime`, `$enableSearch`, `$asideCookieName`,
  `$asideCookieSecure`, `Module::current()` and `Modules\ModuleTrait`
- Removed `UrlManager::$i18nSubdomain` and `hasI18nUrls()`; `$languages` is `?array` (`getLanguages()`), `$defaultLanguage` is `?string`
  and no longer defaults to `sourceLanguage`, `setApplicationLanguage()` is the protected `setLanguage()`, `getDraftHostInfo()` returns `string`
- Removed `Web\Request::getRemoteIP()`: the client IP comes from Yii's `Request::$trustedHosts`; added `Request::$environments`,
  `getEnvironment()`, `getEnvironmentName()`, `ENVIRONMENT_LOCAL` and `ENVIRONMENT_STAGE`
- Renamed the auto login cookie to `_auth` and the session cookie to `_session`; added `Web\User::$cookieSecure` and
  `Web\SessionTrait::$cookieSecure`; cookies carry `secure` over HTTPS; everyone is logged out once on deploy
- Added `Web\Controller::$strictTransportSecurity` (`max-age=31536000`, `false` to opt out) and `warning()`; `error()`, `success()`
  and `errorOrSuccess()` return `static`, take `string|Stringable` and encode a plain string
- Moved the sitemap out of the models: `Web\Sitemap` is `Sitemap\Sitemap`, `models` is `sitemaps` holding `Sitemap\SitemapInterface`
  objects (`ModelSitemap`, `UrlSitemap`); removed `Behaviors\SitemapBehavior` and `Models\Interfaces\SitemapInterface`; an unknown key is a 404
- Moved translated attributes into the `translation` table (`Models\Translation`, `TranslationInterface`, `TranslationTrait`): `name_de` is
  a virtual attribute, `I18nActiveQuery::replaceI18nAttributes()` is `withTranslations()` / `withoutTranslations()`, `getI18nAttribute()` takes a third `bool $fallback`
- Removed the `enableI18nTables` feature: `Modules\ModuleTrait::$enableI18nTables`, `$tablePrefix`, `getTableName()`, `getLanguages()` and `getI18nClassName()`
- Changed `Db\Traits\MigrationTrait`: removed `addI18nColumns()` and `dropI18nColumns()`; added `moveI18nColumnsToTranslations()` and
  `restoreI18nColumnsFromTranslations()` (`(string $table, string $modelClass)`), `addPermission()`, `replaceAuthItems()`, `restoreAuthItems()`,
  `addCustomAttributesColumn()`, `moveColumnsToCustomAttributes()`, `restoreColumnsFromCustomAttributes()`, `dropIndexesContainingColumn()` and `addColumnIfMissing()`
- Removed `Base\Traits\ModelTrait::getTraitNames()`, `getTraitRules()` and `getTraitAttributeLabels()`; a trait's rules and labels are spread by the using class
- Removed `Models\Traits\IconFilenameAttributeTrait` and `ICON_FILENAME_ATTRIBUTE_ICON`; `Models\CustomAttributes\IconCustomAttribute` replaces it
- Removed `Helpers\ArrayHelper::simpleXmlToArray()`, `cacheStringToArray()` and `createCacheString()`; `Xml\XmlNode` replaces the first
- Changed the materialized tree `path` to a JSON array column (`MaterializedTreeTrait::$path` and `getAncestorIds()` hold `int[]`);
  removed `getIdsFromPath()` and `getPathFromIds()`; `NestedTreeTrait` maintains a new `depth` column
- Renamed `Models\Redirect::sanitizeUrl()` to `Helpers\Url::sanitize()` and `getDisplayName()` to `getAdminName()`; a `redirect.request_uri`
  may name a host (`www.example.com/old`); `Redirect` is no longer searchable
- Changed `Models\Forms\DeleteForm::formName()` to `''`, so the confirmation input is a top-level `value`; added `Models\Forms\AccountDeleteForm`
- Split the account page into `AccountController::actionUpdate()`, `actionCredentials()` and `actionSecurity()` with `Models\Forms\AccountCredentialsForm`;
  `UserFormTrait` lost `$repeatPassword` and gained `getUserAttributeNames()`; `UserController::actionDisableGoogleAuthenticator()` is `actionDisableAuthenticator()`
- Dropped the `_` prefix of every private and protected property; inside a class using `MaterializedTreeTrait`, `NestedTreeTrait` or
  `CustomAttributesTrait`, `$this->ancestors`, `$this->children`, `$this->descendants` and `$this->customAttributes` read the cache, so call the getter
- Changed `Web\Request::post()` and `getBodyParams()` to always answer an array
- Changed `Db\ActiveRecord::instantiate()` to build loaded records through the container, so a container definition applies to `find()` too
- Changed `AttributeTypecastBehavior` to cast at fixed points — `Db\ActiveRecord::EVENT_AFTER_LOAD` (new, triggered by `load()`), before validation and before insert/update —
  and only where nothing is lost (`"abc"` stays for the `integer` rule to reject); a `decimal` column is a string at its scale; removed `$typecastBeforeValidate`,
  `$typecastAfterValidate`, `$typecastBeforeSave`, `$typecastAfterSave`, `$typecastAfterFind`, `$skipOnNull` and `$typecastBooleanAsInteger`
- Added `Web\Controller::EVENT_CONFIGURE` and `Widgets\Widget::EVENT_CONFIGURE`; `DashboardController::addRoles()` accepts a `Closure`
- Changed `Widgets\Forms\ActiveForm::$rows` to `$fieldsets` (`list<Fieldset>`); a subclass declares its rows in `getDefaultRows()`;
  `rows()`, `Fieldset::rows()` and `GridView::columns()` take a `Closure`; `ActiveForm::getFieldset()` is `createFieldset()`
- Changed `Widgets\Navs\Traits\ItemTrait` to keep named items (`addItem(name: ...)` replaces, `removeItem()`, `getItem()`); added
  `NavItem::addRoute()`, `Submenu::backUrl()` and `hideSingleItem()`
- Changed the login: one message for every failure (`Web\User::$enableUserEnumerationProtection`), rate limited per email and IP
  (`$loginAttemptLimit`, `$loginAttemptDuration`, in the `cache` component); a password change, reset or 2FA removal ends every other session
- Changed a password reset, an email confirmation and a signup to refuse the automatic login when the account owes a second factor
- Changed the credentials mail to carry a reset link, never a password; a user created in the admin needs no password
- Changed `Web\User::renewIdentityCookie()` to drop a stale cookie; the logout deletes the cookie in every scope and `Web\Response`
  deletes a host-only twin of the session and CSRF cookies
- Changed `Web\Response::redirect()` to answer an htmx request with `HX-Location`; `setHtmxRedirectTarget(null)` sends an ordinary
  redirect and `setHtmxReload()` asks for a full document load, which every login and logout does
- Replaced `yii\log\FileTarget` with `Log\FileTarget` under the target key `file` (UTC timestamps, `$maskQueryParams`, wildcard
  `maskVars`); `logVars` drops `_COOKIE` and `_SESSION`; added `Log\SentryTarget` under `sentry` when `params.sentryDsn` is set
- Changed `Console\Controllers\MigrateController` to refuse a database whose history names classes that no longer load and to run
  `$upgradeFile` (`@root/upgrade/collapse.php`) after a backup when it exists; added `Db\Connection::$lockWaitTimeout`
- Changed `user.login_count` to an unsigned `int`
- Added custom attributes: `Models\Interfaces\CustomAttributeInterface` + `Models\Traits\CustomAttributesTrait` declare typed definitions
  (`Models\CustomAttributes\Text`, `Html`, `Boolean`, `Number`, `Select`, `Icon`, `Url`, `Email`, `HexColor`, `Upload` and `Group`, all
  suffixed `CustomAttribute`) stored in one `custom_attributes` JSON column; a translatable one keeps its `_de` name inside the JSON
- Added the `upload` component (`Upload\Upload`) owning `@webroot/attachments` and the temporary directory; `Web\ChunkedUploadedFile`
  lost `$partialUploadPath`, `$tempFileLifetime`, `$gcProbability` and `removeAbortedFiles()` to it
- Split `Web\StreamUploadedFile` (a request URL, `http`/`https` only, no private host) from the new `Web\CopiedUploadedFile`
  (a path the application names), both over `Web\AbstractUploadedFile`
- Added the fulltext search: `Models\Interfaces\SearchableInterface` + `Models\Traits\SearchableTrait`, `Behaviors\SearchBehavior`, the
  `search` component (`Search\Search`), the `search` table, the admin search, `Modules\Admin\Module::$enableSearch`, `search/rebuild` and `search/clear`
- Added `StatusAttributeInterface::getNextStatus()` and `isStatusUpdatable()`, `Widgets\Grids\Columns\StatusIconColumn::enableUpdate()`,
  `GridView::$enableStatusUpdate` and `Web\Traits\StatusControllerTrait::updateStatus()`
- Added `TypeAttributeTrait::instantiateByType()` and `instantiateFromPost()`; `Behaviors\TrailBehavior::createTrail()` takes the type;
  every class in a type family must answer the same `formName()`
- Added `Db\ActiveQuery::selectWith()`, `Helpers\EventHelper::on()`, `Helpers\SecretKey`, `Helpers\VersionHelper`, `Helpers\NamespaceHelper`,
  `Html\Svg`, `Html\Img::fetchPriority()` and `Html\Custom\RelativeTime` (replacing `yii2-timeago`)
- Added the system page tabs `system/index`, `system/server` and `system/maintenance` over `Widgets\Panels\InfoList`, and the
  `MigrationAlert`, `DirectoryAlert`, `SentryAlert`, `EnvironmentAlert` and `HintAlert` widgets
- Added `user.show_hints` (`User::showsHints()`) and `user.color_scheme` (`User::getColorScheme()`)
- Added `Widgets\AdminLink` (moved from `yii2-cms`, default class `admin`) beside `Widgets\Buttons\AdminButton`, which is invisible to a guest
- Added `Web\Application::current()`, `Console\Application::current()`, `Web\User::current()`, `Web\Request::current()` and `Base\RootPackage`
- Added the console commands `redirect/clean`, `registry/push`, `registry/show`, `search/rebuild`, `search/clear`, `upgrade/passwords`,
  `upload/clear`, `user/password`, `user-login/clear` and `user-token/clear`; `user/create` takes `--name`, `--email` and `--password`
- Added `Widgets\Forms\Fields\AutocompleteField`, `CheckboxListField`, `GroupField` and `UploadField`, `Widgets\Grids\Traits\SelectionTrait`,
  `Widgets\Navs\ModelHeader`, `Modules\Admin\Widgets\Navs\NavBarSearch`, `Modules\Admin\Widgets\Buttons\AsidePinButton` and `LanguageDropdownButton`
- Removed `Db\ActiveRecord::updateAttributesBlameable()`; added `updateDenormalizedAttributes()`

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

- Changed `AuthClientSignupForm` and `Hirtz\Skeleton\Modules\Admin\Models\Forms\UserForm` to extend
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