# yii2-skeleton

The core of a Yii 2 based admin platform: two application classes, a database layer (`Db\ActiveRecord`,
`Db\ActiveQuery`, migrations with backups), users with roles, two-factor authentication and rate-limited login, an admin
module with a navbar, aside, grids, forms and a fulltext search, an HTML and widget layer built on htmx 4, translated
and custom attributes, redirects, a trail, a sitemap and a console. Every other `davidhirtz/yii2-*` bundle requires it.
It depends on `yiisoft/yii2`, `yiisoft/yii2-symfonymailer`, `sentry/sentry`, `robthree/twofactorauth` and
`tinymce/tinymce`, and needs PHP 8.3 with `intl`, `openssl`, `simplexml` and `xmlwriter`. Image processing lives in
`davidhirtz/yii2-media`.

## Installation

```bash
composer require davidhirtz/yii2-skeleton
```

The skeleton has no `extra.bootstrap`: it is the application. The two entry scripts build it from a configuration
array; `Base\Traits\ApplicationTrait::preInitInternal()` merges the core components, aliases and the `admin` module into
it and then reads `config/params.php` and `config/db.php` from `basePath`.

```php
// web/index.php
(new Hirtz\Skeleton\Web\Application(require __DIR__ . '/../config/web.php'))->run();

// yii (console entry point, executable)
exit((new Hirtz\Skeleton\Console\Application(require __DIR__ . '/config/console.php'))->run());
```

Then, from the project root:

```bash
./yii params            # writes cookieValidationKey and passwordPepper into config/params.php
./yii migrate/config    # writes config/db.php
./yii migrate           # applies every registered migration, backing up first
./yii user/create       # the first account, prompted or with --name --email --password
./yii search/rebuild    # after adding a searchable model
```

`Web\Application` serves `web/` (`@webroot`), routes to `App\Controllers`, and turns on the `debug` module under `YII_DEBUG`.
`Console\Application` routes to `App\Commands`, drops the `user` and `session` components, and registers the commands
below. Both throw from `current()` when reached under the wrong SAPI; `Web\User::current()` and `Web\Request::current()`
answer `null` there instead.

## Project layout

| Path | Alias | Holds |
|---|---|---|
| `app/Controllers/` | `@app/Controllers` | `App\Controllers\*`, the web controllers (`site/index` is `SiteController::actionIndex()`) |
| `app/Commands/` | `@app/Commands` | `App\Commands\*`, console commands |
| `app/Migrations/` | `@App/Migrations` (registered during a migration run only) | `App\Migrations\M...`, created with `./yii migrate/create Name` |
| `app/` | `@app` | everything else under `App\`: models, a project admin module (`App\Modules\Admin\Module extends Base\Module`, views under `@views/admin`) |
| `resources/views/` | `@views` | views and layouts; `@views/<controller id>/<view>.php` |
| `messages/` | `@messages` | `messages/<language>/app.php`, the project's `app` category |
| `config/params.php`, `config/db.php` | | read by the application; both gitignored |
| `runtime/`, `web/` | `@runtime`, `@webroot` | logs, backups, uploads temp, maintenance stub; published assets, `attachments/` |

## Configuration

`config/params.php`:

| Key | Default | Meaning |
|---|---|---|
| `cookieValidationKey` | required | Yii's request key; `./yii params` generates it |
| `passwordPepper` | none | appended to every password before hashing; `./yii params/pepper` |
| `secretKey` | `cookieValidationKey` | encrypts 2FA secrets and signs tokens |
| `adminAlias` | `admin` | the URL prefix of the admin module |
| `email` | `hostmaster@<server name>` | the sender of every mail |
| `mailerDsn` | `sendmail://default` | the Symfony mailer transport |
| `cookieDomain` | none | the `Domain` of the session and auth cookies |
| `cacheKeyPrefix` | none | `keyPrefix` of the cache component |
| `sentryDsn` | none | adds `Log\SentryTarget` under `components.log.targets.sentry` |
| `twoFactorAuthenticationIssuer` | the application name | shown in the authenticator app |
| `registryUrl`, `registryKey` | none | where `./yii registry/push` reports the installation |

`modules.admin` (`Modules\Admin\Module`):

| Property | Default | Meaning |
|---|---|---|
| `enableSearch` | `true` | the navbar search, the search actions and the index writes |
| `languages` | `null` (the `i18n` languages) | the languages the admin is offered in; one language hides the picker |
| `languageSessionKey` | `language` | session key of the picked admin language |
| `asideCookieName`, `asideCookieSecure` | `_aside`, `null` | the collapsed-aside cookie |
| `trailLifetime`, `userLoginLifetime` | `false` | seconds `trail/clear` and `user-login/clear` keep rows for |

`components.user` (`Web\User`): `enableLogin`, `enableSignup` (`false`), `enablePasswordReset`, `enableUnconfirmedEmailLogin`,
`enableTwoFactorAuthentication`, `enableUserEnumerationProtection` (all `true` unless noted), `loginAttemptLimit` (10, `0` off)
and `loginAttemptDuration` (900 s) counted per email and IP in the cache, `cookieLifetime` (30 days), `cookieSecure` (`null`
derives from the request), `disableRbacForGuests`, `disableRbacForOwner`. The identity cookie is `_auth`, the session cookie `_session`.

Other components the skeleton configures: `request` (`Web\Request`, `environments` maps host patterns to `local` and `stage`,
`trustedHosts` must be set behind a proxy), `urlManager` (`Web\UrlManager`: `i18nUrl`, `defaultLanguage`, `draftSubdomain`,
`redirectMap`), `i18n` (`I18n\I18N::$languages`), `db` (`Db\Connection`: `backupOnMigration`, `backupPath`, `maxBackups`),
`session` (`Web\DbSession`), `search` (`Search\Search::$models`, `$driver`), `sitemap` (`Sitemap\Sitemap::$sitemaps`, `urls`,
`views`, `useSitemapIndex`), `upload` (`Upload\Upload`: `path`, `maxSize`, `enableStreamUploads`, `uploadLimit`),
`view` (`Web\View::$titleTemplate`), `log` (targets `file` and `sentry`).

A model is configured through the container rather than subclassed; a type's name is a `Yii::t()` result, hence the closure:

```php
'container' => [
    'definitions' => [
        Hirtz\Skeleton\Models\User::class => [
            'customAttributes' => [
                Hirtz\Skeleton\Models\CustomAttributes\TextCustomAttribute::make('company')->max(100),
            ],
        ],
        App\Models\Product::class => [
            'i18nAttributes' => ['name', 'slug'],
            'types' => fn (): array => [
                Hirtz\Skeleton\Models\Types\Type::make(1)->name(Yii::t('app', 'PRODUCT_TYPE_PHYSICAL')),
            ],
        ],
    ],
],
```

## Console commands

| Command | Purpose |
|---|---|
| `migrate`, `migrate/create`, `migrate/config`, `migrate/backup`, `migrate/restore` | migrations with a backup first; `--skipBackup`, `--dbFile`, `--upgradeFile` |
| `params`, `params/cookie`, `params/pepper`, `params/create`, `params/update`, `params/delete` | maintain `config/params.php` |
| `user/create`, `user/password <email>` | create an account, set a password (`--password` or `YII_USER_PASSWORD`) |
| `upgrade/passwords` | mail a reset link to every user without a password |
| `search/rebuild`, `search/clear` | the fulltext index, optionally `--models=Entry,Category` |
| `trail/clear`, `trail/optimize`, `trail/update-models` | trail retention and class renames |
| `user-login/clear`, `user-token/clear`, `upload/clear` | login history, expired tokens, abandoned uploads |
| `redirect/clean` | delete redirect loops and shorten chains; `--hosts`, `--dryRun` |
| `maintenance/enable`, `maintenance/disable`, `maintenance` | the pre-rendered maintenance page (`runtime/maintenance.php`) |
| `registry/push`, `registry/show` | report the installation to a version registry; `--url`, `--strict` |
| `asset/clear`, `email/test <address>`, `message` | published assets, the mailer, message extraction |

## The admin module

`Modules\Admin\Module` is mounted as `admin` under `params.adminAlias`. Access is RBAC: one permission per managed model,
named after it (`user`, `redirect`, `authUpdate`, `trailIndex`, `system`), and three flat roles, `admin` (everything),
`manager` (everything but `system` and `tenant`) and, from the cms bundle, `author`. A role lists permissions, never
another role. A project adds a permission with `Db\Traits\MigrationTrait::addPermission()` and an `AUTH_<NAME>_DESCRIPTION`
message key, guards a controller with `AccessControl` naming `Model::AUTH_<MODEL>`, and never passes a record to `can()`.

Extension points: a bundle module implementing `Modules\Admin\ModuleInterface` contributes `aside(Nav)` items and
`dashboard(Dashboard)` panels; any widget is changed from the outside through `Widgets\Widget::EVENT_CONFIGURE`
(`Helpers\EventHelper::on(NavBar::class, Widget::EVENT_CONFIGURE, fn (NavBar $navBar) => ...)`), any web controller
through `Web\Controller::EVENT_CONFIGURE`; `Modules\Admin\Controllers\DashboardController::addRoles()` widens the
dashboard's access rule. Models opt into features by interface plus trait: `AdminModelInterface`, `TypeAttributeInterface`,
`StatusAttributeInterface`, `CustomAttributeInterface`, `TranslationInterface`, `TrailModelInterface`, `SearchableInterface`.
