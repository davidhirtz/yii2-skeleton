# Upgrading to 3.0

Class names below are relative to `Hirtz\Skeleton\` (v3) and `davidhirtz\yii2\skeleton\` (v2) unless they carry another
namespace. The v2 → v3 database migrations do not ship with the bundle: `davidhirtz/yii2-upgrade` generates them into the
project (see *Data and schema*), and the bundle itself carries one baseline migration for a fresh install.

## Requirements

- PHP `^8.3` with `ext-intl` (new), `ext-json`, `ext-openssl`, `ext-simplexml` and `ext-xmlwriter`.
- MySQL or MariaDB with InnoDB fulltext support; the `search` table carries two `FULLTEXT` indexes.
- Every sibling bundle at `^3.0`. All bundles renamed their namespace at once, so a project upgrades them together.
- `davidhirtz/yii2-upgrade` as a `require-dev` dependency for the length of the upgrade.
- Remove from the project's `composer.json` what v3 dropped: `yiisoft/yii2-authclient`, `yiisoft/yii2-bootstrap4`,
  `davidhirtz/yii2-timeago`, and the asset-packagist repository if it only served jQuery. `bower-asset/jquery` is
  `provide`d by the skeleton and never served; the admin runs on htmx 4 without jQuery, Bootstrap or jQuery UI.
- A deployment behind a proxy must set `components.request.trustedHosts` (see *Configuration*): the spoofable
  `Request::getRemoteIP()` override is gone, and without trusted hosts the client IP, the `secure` cookie flag and the
  HSTS header all see a plain HTTP request from the proxy.

## Renames

### Namespaces and directories

| v2 | v3 |
|---|---|
| `davidhirtz\yii2\skeleton\` | `Hirtz\Skeleton\` |
| lowercase directories (`models\forms\`, `modules\admin\controllers\`) | StudlyCase (`Models\Forms\`, `Modules\Admin\Controllers\`) |
| `controllers\AccountController` | `Modules\Admin\Controllers\AccountController` |
| `codeception\fixtures\UserFixture`, `codeception\fixtures\UserFixtureTrait`, `codeception\traits\StdOutBufferControllerTrait` | `Test\Fixtures\UserFixture`, `Test\Traits\UserFixtureTrait`, `Test\Traits\StdOutBufferControllerTrait` |
| `src/views/`, `src/mail/`, `src/modules/admin/views/` | `resources/views/`, `resources/mail/`, `resources/views/admin/` |
| `src/messages/` | `messages/` |
| project controllers `app\controllers` | `App\Controllers` (`Web\Application::$controllerNamespace`) |
| project commands `app\commands` | `App\Commands` (`Console\Application::$controllerNamespace`) |
| project migrations | `App\Migrations` in `app/Migrations` |
| `@App` alias | removed; `@app` names `app/` |

### Classes

| v2 | v3 |
|---|---|
| `web\Sitemap` | `Sitemap\Sitemap` |
| `behaviors\SitemapBehavior`, `models\interfaces\SitemapInterface` | removed; `Sitemap\ModelSitemap`, `Sitemap\UrlSitemap`, `Sitemap\SitemapInterface` |
| `models\forms\GoogleAuthenticatorForm` | `Models\Forms\TwoFactorAuthenticatorForm` |
| `validators\GoogleAuthenticatorValidator` | `Validators\TwoFactorAuthenticationValidator` |
| `modules\admin\widgets\forms\GoogleAuthenticatorActiveForm`, `GoogleAuthenticatorLoginActiveForm` | `Modules\Admin\Widgets\Forms\TwoFactorAuthenticatorActiveForm`, `TwoFactorAuthenticationLoginActiveForm` |
| `models\traits\IconFilenameAttributeTrait` | removed; `Models\CustomAttributes\IconCustomAttribute` |
| `rbac\rules\OwnerRule` | removed; `Web\User::canManageUser()` |
| `behaviors\UserLanguageBehavior` | removed; `Modules\Admin\Module::$languages` |
| `modules\ModuleTrait` (per-language tables) | `Modules\ModuleTrait` (the static `getModule()` accessor only) |
| `helpers\StructuredData` | `Widgets\StructuredData\BreadcrumbList` |
| `helpers\ArrayHelper::simpleXmlToArray()` | `Xml\XmlNode` |
| `modules\admin\widgets\grids\GridView` | `Widgets\Grids\GridView` |
| `modules\admin\widgets\grids\columns\CounterColumn`, `LinkDataColumn`, `traits\StatusGridViewTrait`, `TypeGridViewTrait`, `MessageSourceTrait` | removed; `Widgets\Grids\Columns\*`, `Widgets\Grids\Toolbars\StatusFilterDropdown`, `TypeFilterDropdown` |
| `modules\admin\widgets\forms\traits\ContentFieldTrait`, `EmailFieldTrait`, `StatusFieldTrait`, `TypeFieldTrait`, `SubmitButtonTrait`, `ModelTimestampTrait` | removed; `Widgets\Forms\ActiveForm`, `Widgets\Forms\Fields\*`, `Widgets\Forms\Footers\*` |
| `modules\admin\widgets\navs\TrailSubmenu`, `panels\HelpPanel`, `UserHelpPanel`, `UserDeletePanel`, `UserOwnerPanel` | removed; `Modules\Admin\Widgets\Navs\TrailHeader`, `UserActionDropdown`, `UserOwnerButton`, `Modules\Admin\Widgets\HintAlert` |
| `widgets\AdminButton` | `Widgets\Buttons\AdminButton` |
| `widgets\bootstrap\ActiveForm`, `ActiveField` | `Widgets\Forms\ActiveForm`, `Widgets\Forms\Fields\InputField` and siblings |
| `widgets\bootstrap\Breadcrumbs`, `Flashes`, `Panel` | `Widgets\Navs\Breadcrumbs`, `Widgets\Flashes`, `Widgets\Panels\Panel` |
| `widgets\bootstrap\ButtonDropdown` | `Widgets\Navs\Dropdown`, `Widgets\Navs\ActionDropdown` |
| `widgets\bootstrap\ListGroup` | removed |
| `widgets\fontawesome\Icon`, `Nav`, `Submenu` | `Widgets\Icon`, `Widgets\Navs\Nav`, `Widgets\Navs\Submenu` |
| `widgets\forms\DeleteActiveForm` | `Widgets\Forms\DeleteActiveForm` |
| `widgets\forms\DynamicRangeDropdown` | `Widgets\Forms\Fields\SelectField` |
| `widgets\forms\FileUpload` | `Widgets\Buttons\FileUploadButton`, `Html\Custom\FileUpload` |
| `widgets\forms\HexColorInputWidget`, `TimezoneDropdown`, `TinyMceEditor` | `Widgets\Forms\Fields\HexColorField`, `TimezoneSelectField`, `TinyMceField` |
| `widgets\jui\DatePicker` | `Widgets\Forms\Fields\DateTimeField` |
| `widgets\pagers\LinkPager` | `Widgets\Grids\Pagers\LinkPager` |
| `assets\AdminAsset`, `FontAwesomeAsset`, `SignupAsset`, `FileUploadAsset` | `Assets\AdminAssetBundle`, `FontAwesomeAssetBundle`, `SignupAssetBundle`, `FileUploadAssetBundle` |
| `assets\BootboxAsset`, `JuiAsset`, `TimeZoneDetectAsset` | removed |
| `auth\clients\*`, `models\AuthClient`, `models\forms\AuthClientSignupForm`, `models\forms\UserPictureForm`, `gii\*` | removed |
| `log\ActiveRecordErrorLogger` | `Log\ActiveRecordErrorLogger`; `yii\log\FileTarget` in the core config is `Log\FileTarget` |

### Methods

| v2 | v3 |
|---|---|
| `User::generateVerificationToken()`, `generatePasswordResetToken()` | `createVerificationToken(): string`, `createPasswordResetToken(): string` |
| `User::getEmailConfirmationUrl()`, `getPasswordResetUrl()` | `createEmailConfirmationUrl()`, `createPasswordResetUrl()` (each issues a token) |
| `User::getTrailModelName()`, `getTrailModelType()` | `getAdminName()`, `getAdminType()` |
| `User::getFullName()`, `getCountries()`, `deletePicture()`, `getPictureUrl()`, `getUploadPath()`, `setUploadPath()`, `getAuthClients()` | removed |
| `$user->google_2fa_secret` | `getTwoFactorAuthenticationSecret()`, `setTwoFactorAuthenticationSecret()`, `hasTwoFactorAuthentication()` |
| `Trail::getModelClass()`, `getDataModelClass()` (returned the record) | `getModelRecord()`, `getDataModelRecord()` |
| `Trail::createOrderTrail(?ActiveRecord, ?string $message, array $data)` | `createOrderTrail(?TrailModelInterface, ?Message, array)` |
| `TrailModelCollection::getModelByNameAndId()` | `getModelByClassAndId()` |
| `Redirect::sanitizeUrl()`, `getDisplayName()` | `Helpers\Url::sanitize()`, `getAdminName()` |
| `static getTypes()`, `static getStatuses()` returning arrays | instance methods returning `Models\Types\Type[]` / `Models\Statuses\Status[]` |
| `getTypeOptions()['key']` | `$model->getType()?->getKey()` |
| `UrlManager::setApplicationLanguage()` | protected `setLanguage(Request $request)` |
| `UrlManager::hasI18nUrls()`, `Request::getLanguage()`, `getLanguageFromCookie()`, `getRemoteIP()` | removed |
| `I18nActiveQuery::replaceI18nAttributes()` | `withTranslations(array\|string\|null $languages = null)`, `withoutTranslations()` |
| `getI18nAttributeName($attribute, $language)`, `getI18nAttribute($attribute, $language)` | a third `bool $fallback = false` argument |
| `MigrationTrait::addI18nColumns()`, `dropI18nColumns()` | removed; `moveI18nColumnsToTranslations(string $table, string $modelClass)`, `restoreI18nColumnsFromTranslations()` |
| `ModelTrait::getTraitNames()`, `getTraitRules()`, `getTraitAttributeLabels()` | removed; the using class spreads the trait's methods itself |
| `ArrayHelper::cacheStringToArray()`, `createCacheString()`, `MaterializedTreeTrait::getIdsFromPath()`, `getPathFromIds()` | removed; `path` is a JSON `int[]` column |
| `Html::buttonList()`, `Html::buttons()` | removed; `Widgets\Buttons\ButtonGroup` |
| `GridView::isSortedByPosition()` | `isSortable()` |
| `GridView::getUpdateButton()`, `getDeleteButton()`, `getSortableButton()`, `renderSelectionForm()` | removed; `Widgets\Grids\Columns\ButtonColumn`, `Widgets\Grids\Traits\SelectionTrait` |
| `Web\Controller::error()`, `success()`, `errorOrSuccess()` returning `bool` / `void` | return `static`, take `string\|Stringable` and encode a plain string; `warning()` added |
| `Modules\Admin\Module::getDashboardPanels()`, `setDashboardPanels()`, `getNavBarItems()`, `setNavBarItems()` | `Module::dashboard(Dashboard)`, `ModuleInterface::aside(Nav)`, `Widget::EVENT_CONFIGURE` on `Modules\Admin\Widgets\Navs\NavBar` |
| `UserController::actionDisableGoogleAuthenticator()` | `actionDisableAuthenticator()` (POST only) |
| `AccountController::actionUpdate()` with email and password fields | `actionUpdate()`, `actionCredentials()`, `actionSecurity()` |
| `ActiveForm::getFieldset()` | `createFieldset()` |

### Properties

| v2 | v3 |
|---|---|
| `Web\User::$enableGoogleAuthenticator` | `$enableTwoFactorAuthentication` |
| `Web\User::$loginType` (`string`, `'unknown'`) | `int`, `UserLogin::TYPE_OTHER` |
| `Modules\Admin\Module::$alias` | `params['adminAlias']` |
| `Modules\Admin\Module::$showInBreadcrumbs` | removed |
| `Modules\Admin\Module::$trailLifetime` (`?int`) | `int\|false` |
| `UrlManager::$i18nSubdomain` | removed |
| `UrlManager::$languages` (`array\|false\|null`) | `?array`, read through `getLanguages()` |
| `UrlManager::$defaultLanguage` (`string\|false\|null`, defaulted to `sourceLanguage`) | `?string`; `null` lets `Accept-Language` decide |
| `Sitemap::$models` | `Sitemap\Sitemap::$sitemaps` |
| `Widgets\Forms\ActiveForm::$rows` | `$fieldsets` (`list<Fieldset>\|null`), declared through `getDefaultRows()` |
| `UserFormTrait::$repeatPassword`, `$upload` | removed |
| `LoginForm::$enableFacebookLogin`, `SignupForm::$enableFacebookSignup` | removed |
| `ChunkedUploadedFile::$partialUploadPath`, `$tempFileLifetime`, `$gcProbability`; `StreamUploadedFile::$temporaryUploadPath` | `Upload\Upload::$tempPath`, `$tempLifetime`, `$enableGarbageCollection` |
| `AdminButton::$icon` (`string`) | `string\|Stringable` |
| private and protected `$_name` | `$name` |

### Constants

| v2 | v3 |
|---|---|
| `User::AUTH_USER_CREATE`, `AUTH_USER_UPDATE`, `AUTH_USER_DELETE` | `User::AUTH_USER` (`user`) |
| `Redirect::AUTH_REDIRECT_CREATE` | `Redirect::AUTH_REDIRECT` (`redirect`) |
| `UserLogin::TYPE_LOGIN` `'login'`, `TYPE_COOKIE` `'auto'`, `TYPE_SIGNUP` `'signup'`, `TYPE_CONFIRM_EMAIL` `'email'`, `TYPE_RESET_PASSWORD` `'password'` | `2`, `3`, `4`, `5`, `6`; `TYPE_OTHER` `1` |
| `Trail::TYPE_DEFAULT` (resolved to `1`, a create) | `13`, a plain message |
| `Widget::ROLE_ANY` | `Models\User::ROLE_ANY` (`*`), `ROLE_AUTHENTICATED` (`@`) |
| — | `User::AUTH_ROLE_MANAGER` (`manager`), `User::PASSWORD_PEPPER`, `Modules\Admin\Module::AUTH_SYSTEM` (`system`) |

### Configuration keys and params

| v2 | v3 |
|---|---|
| `modules.admin.alias` | `params.adminAlias` |
| `params.googleAuthenticatorIssuer` | `params.twoFactorAuthenticationIssuer` |
| `params.facebookClientId`, `params.facebookClientSecret`, `components.authClientCollection` | removed |
| `components.sitemap.models` | `components.sitemap.sitemaps` |
| `components.log.targets[0]` (the file target) | `components.log.targets.file` |
| `modules.<id>.enableI18nTables`, `modules.<id>.tablePrefix` | removed |
| — | `params.secretKey`, `passwordPepper`, `sentryDsn`, `mailerDsn`, `registryUrl`, `registryKey`; `components.upload`, `components.search`; `components.user.cookieSecure`, `components.session.cookieSecure` |

### Tables and columns

| v2 | v3 |
|---|---|
| `user.password_salt` | `user.password_scheme` (`pepper` or `NULL`, never a salt) |
| `user.google_2fa_secret` (16 chars, clear) | `user.two_factor_secret` (encrypted, `enc:` prefix) |
| `user.verification_token`, `password_reset_token`, `google_2fa_recovery_codes` | `user_token` (`user_id`, `type`, hashed `token`, `expires_at`) |
| `user.picture`, `first_name`, `last_name`, `birthdate`, `city`, `country` | `user.custom_attributes` (JSON) |
| `user.login_count` (`smallint`) | unsigned `int` |
| `user_login.type` (`string(12)`) | `tinyint` |
| `trail.model`, `translation.model` | `model_class` |
| `<table>.<attribute>_<language>` columns | `translation` (`model_class`, `model_id`, `language`, `attribute`, `value`) |
| `auth_client` | dropped |
| `auth_rule` rows, `auth_item.rule_name` | emptied and cleared |
| materialized `path` (comma string) | JSON array |
| — | `user.email_confirmed_at`, `show_hints`, `color_scheme`; `search`; `depth` on every nested tree table |

### Message keys

The `skeleton` category is key-based: every string is an `UPPER_SNAKE_CASE`, domain-first key looked up with
`forceTranslation`, and `messages/en-US/skeleton.php` is the catalogue of the English copy. There is no mechanical map
from the old English text; grep the new file for the wording. Shapes: `{DOMAIN}_{ATTRIBUTE}_LABEL` / `_HINT` / `_ERROR`,
`{DOMAIN}_SUCCESS_*`, `{DOMAIN}_CONFIRM_*`, `{DOMAIN}_BUTTON_*`, `AUTH_{PERMISSION}_DESCRIPTION`, `COMMON_*`. The
`countries` category is `country`. Dropped languages: `ru`, `zh-CN`, `zh-TW`.

### Console commands

| v2 | v3 |
|---|---|
| `trail/update-models --filter='\models\'` | default filter is `'\Models\'` |
| — | `migrate` options `--dbFile`, `--upgradeFile`, `--skipBackup` |
| — | `params/pepper`, `redirect/clean`, `registry/push`, `registry/show`, `search/rebuild`, `search/clear`, `upgrade/passwords`, `upload/clear`, `user/password <email>`, `user-login/clear`, `user-token/clear` |
| `user/create` (prompts) | also `--name`, `--email`, `--password` or the `YII_USER_PASSWORD` environment variable |

### Cookies and CSS

| v2 | v3 |
|---|---|
| `_identity` (auto login), `PHPSESSID` (session) | `_auth`, `_session` |
| `.admin.overlay` (project-defined `overlay`) | `.admin`, positioned by `AdminButton::registerCss()`; the `relative` ancestor stays the project's |

## Configuration

The entry scripts name the base path, which the application would otherwise take from the request (`web/index.php`)
or the working directory (`yii`) — PHP's built-in server reports a requested static file as the script, and a cron job
runs from anywhere. A `basePath` in the config still wins:

```php
// web/index.php
(new Application(['basePath' => dirname(__DIR__), ...require __DIR__ . "/../config/$env.php"]))->run();

// yii
exit((int)(new Application(['basePath' => __DIR__, ...require __DIR__ . "/config/$env.php"]))->run());
```

`config/params.php`:

```php
return [
    'cookieValidationKey' => '...',           // required; `./yii params` generates it
    'passwordPepper' => '...',                // `./yii params/pepper` generates it; back it up with your secrets
    'secretKey' => '...',                     // optional; encrypts 2FA secrets and signs tokens, falls back to cookieValidationKey
    'adminAlias' => 'admin',                  // was modules.admin.alias
    'twoFactorAuthenticationIssuer' => 'My site', // was googleAuthenticatorIssuer
    'sentryDsn' => 'https://...',             // optional; adds Log\SentryTarget under components.log.targets.sentry
];
```

`config/web.php` (or wherever the project configures components):

```php
'components' => [
    'request' => ['trustedHosts' => ['10.0.0.0/8']],      // behind a proxy; Yii's own proxy handling is the only one left
    'user' => [
        'enableTwoFactorAuthentication' => true,          // was enableGoogleAuthenticator
        'cookieSecure' => null,                           // pin to false on a host answering on http and https
        'loginAttemptLimit' => 10,                        // 0 disables; counters live in the cache component
        'enableUserEnumerationProtection' => true,        // false restores the messages that name the reason
    ],
    'session' => ['cookieSecure' => null],
    'sitemap' => [
        'sitemaps' => [                                   // was models
            'products' => [
                'class' => ModelSitemap::class,
                'modelClass' => Product::class,
                'url' => fn (Product $product) => ['loc' => $product->getRoute()],
                'changeFrequency' => 'weekly',
                'priority' => 0.8,
            ],
        ],
    ],
    'upload' => ['enableStreamUploads' => false],         // optional; turns the URL import off
    'i18n' => [
        'translations' => [
            'app' => ['class' => PhpMessageSource::class, 'basePath' => '@messages', 'forceTranslation' => true],
        ],
    ],
],
'modules' => [
    'admin' => ['languages' => ['de', 'en-US']],           // the admin's own list; defaults to i18n.languages
],
'container' => [
    'definitions' => [
        User::class => [
            'customAttributes' => [TextCustomAttribute::make('first_name')->max(50)],
        ],
    ],
],
```

The login rate limit and the upload limit count in `components.cache`; a multi-node deployment wants a shared cache. A
console command that builds absolute URLs (`upgrade/passwords`, the sitemap) needs `components.urlManager.hostInfo` and
`baseUrl` in the console configuration.

## Code changes

### Types and statuses are objects

`getTypes()` and `getStatuses()` are instance methods returning definition objects; an array item throws
`InvalidConfigException`, and `getTypeOptions()` is gone. Read through `getTypeDefinitions()`, `findType()` or
`$model->getType()`; never mutate a definition, every record of that value shares it.

```php
// before
public static function getTypes(): array
{
    return [self::TYPE_DEFAULT => ['name' => Yii::t('app', 'Page'), 'hiddenFields' => ['content', '#assets'], 'class' => Page::class]];
}

// after
public function getTypes(): array
{
    return [Type::make(self::TYPE_DEFAULT)->name(Yii::t('app', 'PAGE'))->hiddenFields('content')->modelClass(Page::class)];
}
```

`hiddenFields()` takes attribute names, not selectors, and a hidden attribute is dropped server-side: unsafe, unvalidated,
unrendered, and its stored value survives a save. A model using `Models\Traits\VisibleAttributeTrait` must declare
`Models\Interfaces\VisibleAttributeInterface`. A project option that lived in the array (`entriesPerPage`) becomes a setter on
a `Type` subclass named by `getTypeClass()`; a narrowing `getType()` override calls `static::normalizeTypeValue()` first. A
type's `available(Closure|bool)` is enforced by `Validators\DynamicRangeValidator`, with the stored value exempt. Small
projects declare types in the container instead of subclassing: `Entry::class => ['types' => fn (): array => [...]]`, a
closure because a name is a `Yii::t()` result. The `Type` constructor also accepts an int-backed enum.

### Custom attributes replace project columns

A model implementing `Models\Interfaces\CustomAttributeInterface` with `Models\Traits\CustomAttributesTrait` declares typed
definitions (`Models\CustomAttributes\TextCustomAttribute::make('subtitle')->translatable()`, `Group`, `Select`, `Upload`, ...)
stored in one `custom_attributes` JSON column. Their values are ordinary attributes (`load()`, `validate()`, the trail,
`getI18nAttribute()`), but not query conditions: a `where`, `orderBy` or grid sort on one has to stay a real column.
Declare them per type through `Type::customAttributes()`, in the container through `customAttributes`, or by overriding
`getCustomAttributes()`. `rules()`, `attributeLabels()` and `attributeHints()` must spread `parent::...`. A translatable
definition names its languages through `translatableAttributes`, never `i18nAttributes`. `IconFilenameAttributeTrait` is
`IconCustomAttribute::make('icon')->path('/images/icons/')`; the `icon_filename` column needs a project migration
(`MigrationTrait::moveColumnsToCustomAttributes()`).

### Translations live in the `translation` table

Only the source language is a column; `name_de` is a virtual attribute read from `translation`. A translated model
implements `Models\Interfaces\TranslationInterface`, uses `Models\Traits\TranslationTrait` beside `I18nAttributesTrait`,
returns `self::class` from `getTranslationModelClass()` and an `I18nActiveQuery` from `find()`. `replaceI18nAttributes()`
is `withTranslations()`; a multi-row query eager loads every language on its own, `withoutTranslations()` opts out.
`getI18nAttribute('name', 'de', fallback: true)` falls back to the source language. Translated attributes must be
string-typed; `''`, `null` and no row all mean no translation. `updateAttributes()` and `batchInsert()` bypass the table.

### Admin model interface

`Models\Interfaces\AdminModelInterface` is how a model presents itself in the admin. `Models\Traits\AdminModelTrait`
implements everything but `getAdminRoute()` and `getPermissionName()`, which every implementing model answers itself; a
model only ever edited through another answers its parent's permission. `TrailModelInterface` and `SearchableInterface`
extend it, so a trail model adds `use AdminModelTrait;` beside `use TrailModelTrait;` and renames `getTrailModelName()`
/ `getTrailModelType()`. `getAdminParent()`, `getAdminIndexBreadcrumb()` and `getAdminSubtitle()` feed
`Widgets\Navs\ModelHeader`, which builds the H1, subtitle and breadcrumbs from the chain.

### Permissions and roles

One permission per admin-managed model, named after the model: `can(User::AUTH_USER)`, never `can('userUpdate')` and
never with a record, except the `user` param the user permissions read through `Web\User::canManageUser()`. A role lists
permissions, not other roles, so an `AccessRule` an administrator should pass names `[User::AUTH_ROLE_ADMIN,
User::AUTH_ROLE_MANAGER]` and a new permission is added to both:
`$this->addPermission(Invoice::AUTH_INVOICE, Message::make('app', 'AUTH_INVOICE_DESCRIPTION'), User::AUTH_ROLE_ADMIN, User::AUTH_ROLE_MANAGER)`.
A description is an `I18n\Message` pointer, so `messages/config.php` lists `Message::make` under `translator` and the key
under `keepMessages`. A per-record `yii\rbac\Rule` of the project's own still works; the platform ships none.

### Tokens, passwords and 2FA

A token is a `Models\UserToken` row; `createVerificationToken()` and `createPasswordResetToken()` return it in the clear
once, so a form that mails one holds it (`Modules\Admin\Models\Forms\UserForm::getPasswordResetUrl()`), and the mail
templates take a `$url`. `isUnconfirmed()` reads `email_confirmed_at`; a fixture marking an account unconfirmed sets that
to `null`. The second factor is reached through `getTwoFactorAuthenticationSecret()` / `setTwoFactorAuthenticationSecret()`
/ `hasTwoFactorAuthentication()`; `TwoFactorAuthenticatorForm::$recoveryCodes` after `save()` is the one moment the codes
exist. A flow logging a user in without a password asks `Web\User::isTwoFactorAuthenticationRequired()` first. A form
using `Models\Traits\IdentityTrait` reports through `addIdentityError()`, and `$form->user` may be `null` after a
successful `validate()`. `User::$passwordMinLength` is 8, `$passwordMaxLength` 72; `password_scheme` is a marker, not a salt.

### Forms

A form subclass declares its rows in `getDefaultRows()` instead of assigning `$this->rows ??=` in `configure()`, and a
listener reaches one fieldset through the closure forms of `rows()` and `Fieldset::rows()`. Mixing bare fields and groups
in one list throws. An action on a typed model guards the save with `!$this->request->isFormReload()`, since
`TypeSelectField` posts the form to the same action on every change. A `DeleteForm` posts `value` at the top level, so
`load($this->request->post())` needs no form name. `UserFormTrait::getUserAttributeNames()` allowlists what a partial user
form may load.

### Widgets, views and extension points

The Yii widget layer is replaced: `Html\` for elements, `Widgets\` for `GridView`, `ActiveForm`, `Nav`, `Submenu`,
`Header`, `Dropdown` and the buttons, all `Stringable` and built with `::make()` plus fluent setters. Views moved to
`resources/views/`, so a project overriding a bundle view points its `viewPath` there; a project admin module extends
`Base\Module` and finds its views under `@views/<module id>`. A widget is extended from the outside through
`Widget::EVENT_CONFIGURE` (`Helpers\EventHelper::on(NavBar::class, Widget::EVENT_CONFIGURE, fn (NavBar $navBar) => ...)`),
a controller through `Web\Controller::EVENT_CONFIGURE`; `DashboardController::addRoles()` takes a closure so a bootstrap
does not autoload every model. `Modules\Admin\ModuleInterface::aside(Nav)` and `dashboard(Dashboard)` replace the
`getNavBarItems()` / `getDashboardPanels()` pairs. `Widgets\AdminLink` (from `yii2-cms`) renders the frontend overlay for
any `AdminModelInterface`; its class is `admin` alone.

### Sitemaps

A sitemap is a `Sitemap\SitemapInterface` object listed under `components.sitemap.sitemaps`; the model carries nothing.
`Sitemap\ModelSitemap` takes `modelClass`, a `url` closure, `changeFrequency`, `priority` and `batchSize`, or a subclass
overrides `getQuery()` and `getRecordUrls()`. The component's `urls` and `views` are `Sitemap\UrlSitemap` under the reserved
key `urls`. Paging is by `getPageCount()`, an unknown key or offset is a 404, the XML namespace is
`http://www.sitemaps.org/schemas/sitemap/0.9`.

### Smaller changes

- `Web\Request::post()` and `getBodyParams()` always answer an array; `load($this->request->post())` needs no object guard.
- `Db\ActiveRecord::instantiate()` builds loaded records through the container, so `i18nAttributes` and `types` configured
  there apply to `find()` as well as `create()`.
- `AttributeTypecastBehavior` casts after `load()` (`Db\ActiveRecord::EVENT_AFTER_LOAD`), before validation and before
  insert/update, with no switches: drop `typecastBeforeValidate`, `typecastAfterValidate`, `typecastBeforeSave`,
  `typecastAfterSave`, `typecastAfterFind`, `skipOnNull` and `typecastBooleanAsInteger` from a behavior config. `null` is
  never cast, a boolean is an integer, a `decimal` column is a string at its scale (`"12.30"`, as MySQL returns it), and
  a value that does not convert without loss is left for the validator. A `filter` rule with `intval` or a `number_format()`
  in `beforeSave()` that only kept an attribute from reading as changed can go; a `filter` rule casting posted input hides
  invalid input, use `integer` / `number`.
- `Web\Controller::error()`, `success()` and `errorOrSuccess()` return `static` and encode a plain string; pass a
  `Stringable` to flash markup. `warning()` is new.
- Code that only runs under one SAPI uses `Web\Application::current()` / `Console\Application::current()` (throwing) or
  `Web\User::current()` / `Web\Request::current()` (nullable) instead of `Yii::$app->has('user') ? ... : null`.
- Inside a class using `MaterializedTreeTrait`, `NestedTreeTrait` or `CustomAttributesTrait`, `$this->ancestors`,
  `$this->children`, `$this->descendants` and `$this->customAttributes` are the private caches; call the getters.
- `Trail::createOrderTrail()` takes a `Message`; `Behaviors\TrailBehavior::createTrail(int $type)` takes the type.
- A record built for a known type goes through `TypeAttributeTrait::instantiateByType()`, a create action through
  `instantiateFromPost($this->request->post(), $type)`; every class in a type family answers the same `formName()`.
- A trait contributing rules or labels is spread explicitly in the using class's `rules()` / `attributeLabels()`.
- `Web\User::$loginType` is an `int`; a project's own login types start at 7 and are declared in a `UserLogin::getTypes()` override.
- `ArrayHelper::simpleXmlToArray($xml)` is `Xml\XmlNode::fromString($xml)`, read with `getChild()`, `getChildren()`,
  `getAttribute()`, or `toArray()` for the old shape; invalid XML throws.
- A `redirect.request_uri` may be host-qualified (`www.example.com/old`); a writer of redirect rows keeps them out of loops
  itself, `./yii redirect/clean` repairs an existing table.
- `Web\CopiedUploadedFile(['path' => ...])` is the class for a path the application names; `Web\StreamUploadedFile` is
  for a request URL only and refuses private hosts unless `upload.allowPrivateStreamUploadHosts` is set.
- `Widgets\Buttons\AdminButton` renders for authenticated users only; `roles([User::ROLE_ANY])` restores the old behaviour.
- A `Yii::t('app', ...)` message source needs `forceTranslation => true` once its keys are key-based; a bundle never writes
  into `app`.

## Data and schema

The bundle ships `Migrations\M260101000000SkeletonBaseline` for a fresh install. A v2 database is upgraded by the
migrations `davidhirtz/yii2-upgrade` generates into `app/Migrations` (its sources are `migrations/yii2-skeleton/` in that
package), plus `upgrade/collapse.php`, which rewrites the `migration` history so the renamed namespaces resolve.
`Console\Controllers\MigrateController` refuses to migrate while the history names classes that no longer load, and runs
`$upgradeFile` (`@root/upgrade/collapse.php`) after a backup when it exists, so an upgrade deployment is an ordinary
`./yii migrate`.

### Before `./yii migrate`

1. Back up (`./yii migrate/backup`). The dropped password hashes and the `auth_client` rows cannot be recovered.
2. Count the users who will lose their password: `SELECT COUNT(*) FROM user WHERE password_salt IS NOT NULL AND password_salt != 'pepper'`.
   On a v2 database that is everyone who ever set one. If they are customers, plan the announcement.
3. `./yii params/pepper` and keep `cookieValidationKey` (and `secretKey`, if set) stable: the 2FA secrets are encrypted with it.
4. Declare the profile columns you keep as custom attributes on `User` (see *Configuration*), named after the column, before
   the migration copies them into `custom_attributes`. Do not open the admin between configuring and migrating: a
   definition colliding with a still-existing column throws.
5. Map the project's own `user_login.type` strings in `params['userLoginTypes'] = ['shibboleth' => 7]`; anything unmapped
   becomes `TYPE_OTHER`.
6. Set `components.urlManager.hostInfo` and `baseUrl` for the console, or `upgrade/passwords` refuses to build links.
7. Export `auth_client` if the linked account ids matter; remove `facebookClientId` / `facebookClientSecret` from the params.

### What the migrations do, in order

1. `M260910100000Translation` creates `translation`; the bundle migrations that follow move each model's `_xx` columns into it.
2. `M260912090000ModelClass` renames `trail.model` and `translation.model` to `model_class` and rewrites the `model` key in `trail.data`.
3. `M260912130000AuthClient` drops `auth_client` and the trails pointing at it.
4. `M260913100000UserAttributes` adds `user.custom_attributes`, copies `first_name`, `last_name`, `birthdate`, `city` and
   `country` into it and drops them with `picture`. Files under `web/uploads/users/` are left for you to delete.
5. `M260913110000TrailType` moves the `trail.type` column default to `13`.
6. `M260913120000Search` creates `search` with its two fulltext indexes.
7. `M260913130000LoginCount` widens `user.login_count` to an unsigned `int`.
8. `M260913140000TokenExpiry`, `M260913160000TwoFactorAuthentication` and `M260913170000UserToken` create `user_token`,
   copy every live verification and reset token and recovery code into it (hashed), add `user.email_confirmed_at`
   backfilled from `updated_at`, encrypt the 2FA secrets and drop the five token columns. Existing links keep working.
9. `M260913150000UserDeleteRule` is a no-op kept for history.
10. `M260913180000PasswordScheme` renames `password_salt` to `password_scheme`, drops every hash that carried a v2 salt and
    rotates those accounts' `auth_key`. Open sessions, roles and 2FA are kept, so the administrator running the upgrade stays logged in.
11. `M260913190000TwoFactorSecret` renames `google_2fa_secret` to `two_factor_secret`.
12. `M260914100000AuthItems` creates `user` and `redirect`, grants each to every parent and assignee of the verb items it
    replaces, deletes those, clears every `rule_name`, empties `auth_rule` and stores the descriptions as `Message` JSON.
    An account holding `userUpdate` alone now manages users outright; review assignments first if that matters.
13. `M260914180000UserLoginType` converts `user_login.type` through the map above.
14. `M260914190000ManagerRole` creates `manager` with every permission and flattens `admin`; `M260914220000SystemPermission` adds `system` to `admin` alone.
15. `M260915130000CustomAttributesColumn` reorders `user.custom_attributes` (cosmetic).
16. `M260917100000ShowHints` and `M260920100000ColorScheme` add `user.show_hints` and `user.color_scheme`.

### After `./yii migrate`

- `./yii search/rebuild` fills the index for every searchable model.
- `./yii upgrade/passwords` mails a reset link to every user without a password; it is safe to repeat. `./yii user/password
  <email>` sets one directly when the mailer is not an option. `Web\User::$enablePasswordReset` must be on for the links to land.
- Add `./yii user-token/clear`, `user-login/clear` and `trail/clear` to cron.
- Everyone logs in again: the cookies are renamed and the remember-me keys rotated.
- Write a project migration for the project's own translated models (`moveI18nColumnsToTranslations()`), icon columns and
  any column that becomes a custom attribute (`moveColumnsToCustomAttributes()`); `yii2-upgrade generate-columns` drafts it.

### What is lost

Every v2 password and remember-me cookie; the `auth_client` table; the provider names in `user_login.type` (`facebook`
becomes `TYPE_OTHER`, on the way down too); the `picture` column and nothing on disk; the Russian and Chinese translations
and their flag images; `migrate/down` past `M260913180000PasswordScheme` restores columns but never hashes.

## Removed

- Social login through `yiisoft/yii2-authclient`, no replacement; a user who only ever signed in that way recovers a password through `account/recover`.
- Profile pictures (`User::$picture`, `UserPictureForm`, `account/picture`, `user/delete-picture`).
- `first_name`, `last_name`, `birthdate`, `city`, `country` as columns, `User::getFullName()`, `getCountries()`.
- Per-language database tables (`enableI18nTables`, `tablePrefix`, `I18N::getTableName()` on the module trait).
- The language query parameter, cookie and `UserLanguageBehavior`; the admin language is a session override picked through `admin/account/language`.
- `Rbac\Rules\OwnerRule` and the verb permissions `userCreate`, `userUpdate`, `userDelete`, `redirectCreate`.
- `Behaviors\SitemapBehavior`, `Models\Interfaces\SitemapInterface`, `getSitemapQuery()` and friends on models.
- `Models\Traits\IconFilenameAttributeTrait`.
- `ArrayHelper::simpleXmlToArray()`, `cacheStringToArray()`, `createCacheString()`; `MaterializedTreeTrait::getIdsFromPath()`, `getPathFromIds()`.
- `ModelTrait::getTraitNames()`, `getTraitRules()`, `getTraitAttributeLabels()`.
- `Html::buttonList()`, `Html::buttons()`, `helpers\StructuredData`, the Bootstrap, Font Awesome and jQuery UI widget classes, the Gii templates.
- `Request::getRemoteIP()`, `Request::getLanguage()`, `UrlManager::$i18nSubdomain`, `UrlManager::hasI18nUrls()`.
- `Models\Redirect` from the search index (opt it back in with `SearchableInterface` + `SearchableTrait`).
- The `ru`, `zh-CN` and `zh-TW` message files.
