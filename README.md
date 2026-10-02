# yii2-skeleton

The core of a Yii 2 based admin platform: two application classes, a database layer (`Db\ActiveRecord`,
`Db\ActiveQuery`, migrations with backups), users with roles, two-factor authentication and rate-limited login, an admin
module with a navbar, aside, grids, forms and a fulltext search, an HTML and widget layer built on htmx 4, translated
and custom attributes, redirects, a trail, a sitemap and a console. Every other `davidhirtz/yii2-*` bundle requires it.
It depends on `yiisoft/yii2`, `symfony/mailer`, `sentry/sentry` and `robthree/twofactorauth` (TinyMCE is bundled into
the admin's compiled assets), and needs PHP 8.3 with `fileinfo`, `intl`, `json`, `openssl`, `pdo_mysql`, `simplexml`
and `xmlwriter`. Image processing lives in
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

`Web\Application` serves `web/` (`@webroot`), routes to `App\Controllers`, and turns on the `debug` module under `YII_DEBUG`
when `yiisoft/yii2-debug` is installed (never in the test environment).
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
| `hostInfo` | none | the installation's canonical URL (`https://www.example.com`), pinned where `urlManager` configures none: absolute URLs never name the host a request claimed, and the console, which has no request, builds them (its `baseUrl` defaults to the root) and reports itself to the registry under it |
| `allowedHosts` | none | comma-separated host names a request may carry (`fnmatch()` patterns: `www.example.com, *.example.com`); any other gets a 400 before routing, local hosts always pass. Without it, or a pinned `hostInfo`, links built from the request (password resets) name whatever host it claimed, and the admin says so |
| `email` | `hostmaster@<host>` | the sender of every mail; the host is the request's, or under the console the one `hostInfo` (or `urlManager.hostInfo`) pins — without either, a console mail needs it set |
| `mailerDsn` | `sendmail://default` | the Symfony mailer transport; any installed bridge's scheme (`resend+api://KEY@default` with `symfony/resend-mailer` and `symfony/http-client`); `native://default` where the host's `sendmail` has no `-bs` mode |
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
| `translationLayout` | `inline` | translated fields side by side (`inline`) or one language at a time (`tabs`) |
| `trailLifetime`, `userLoginLifetime` | `false` | seconds `trail/clear` and `user-login/clear` keep rows for |

`components.user` (`Web\User`): `enableLogin`, `enableSignup` (`false`), `enablePasswordReset`, `enableUnconfirmedEmailLogin`,
`enableTwoFactorAuthentication`, `enableUserEnumerationProtection` (all `true` unless noted), `loginAttemptLimit` (10, `0` off)
and `loginAttemptDuration` (900 s) counted per email and IP in the cache, `enableDeviceCookies` (a browser an account logged
in from is counted on its own, so strangers cannot lock its owner out), `deviceCookieLifetime` (a year), `cookieLifetime`
(30 days), `cookieSecure` (`null` derives from the request), `disableRbacForGuests`, `disableRbacForOwner`. The identity
cookie is `_auth`, the device cookie `_device`, the session cookie `_session`.

Other components the skeleton configures: `request` (`Web\Request`, `environments` maps host patterns to `local` and `stage`,
`trustedHosts` must be set behind a proxy; behind a CDN, key each range by the headers it sets,
`['173.245.48.0/20' => ['X-Forwarded-For', 'X-Forwarded-Proto']]`), `urlManager` (`Web\UrlManager`: `i18nUrl`, `defaultLanguage`, `draftSubdomain`,
`redirectMap`), `i18n` (`I18n\I18N::$languages`), `db` (`Db\Connection`: `backupOnMigration`, `backupPath`, `maxBackups`, `ignoredBackupTables`, `lockWaitTimeout`),
`session` (`Web\DbSession`, its own `cookieSecure`), `search` (`Search\Search::$models`, `$driver`), `sitemap` (`Sitemap\Sitemap::$sitemaps`, `urls`,
`views`, `useSitemapIndex`), `upload` (`Upload\Upload`: `path`, `baseUrl`, `maxSize`, `uploadLimit`, `uploadLimitDuration`, `tempPath`,
`tempLifetime`, `enableGarbageCollection`, and for uploads from a URL `enableStreamUploads`, `streamUploadTimeout`,
`maxStreamUploadSize`, `maxStreamUploadRedirects`, `allowPrivateStreamUploadHosts`),
`view` (`Web\View::$titleTemplate`), `log` (targets `file` and `sentry`).

### Content Security Policy

The admin sends a strict policy, the `contentSecurityPolicy` component (`Web\ContentSecurityPolicy`):
`script-src 'self' 'strict-dynamic' 'nonce-…'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'`. Every script
`Web\View` renders carries the nonce (`registerJs()`, `registerJsFile()`, asset bundles, `registerJsModule()`); a script
added by hand has to as well, `$this->renderScript($js)` or `nonce="<?= $this->nonce ?>"`. An inline event handler
(`onclick`) or a `javascript:` URL is refused. `'strict-dynamic'` trusts whatever a trusted script loads itself, so a
bundle or project widens only the other directives, from its `Bootstrap` or the component's configuration. Styles are
unrestricted until a project adds `style-src` (or `style-src-elem`): the nonce then joins it and `Web\View` stamps it on
every `<style>` it renders, unless the directive allows `'unsafe-inline'`, which a nonce would cancel.

```php
Hirtz\Skeleton\Web\Application::current()->getContentSecurityPolicy()
    ->addSource('frame-src', 'https://www.youtube-nocookie.com');
```

A frontend page sends `Web\Controller::$contentSecurityPolicy`,
`frame-ancestors 'self'; object-src 'none'; base-uri 'self'`, which restricts no script and so survives a page cached for
every visitor. A controller whose pages are never cached as a whole can send the strict policy instead
(`$strictContentSecurityPolicy = true`); a cached page cannot, since every visitor would get the nonce it was cached with.

`Strict-Transport-Security` is the web server's to send, for every response of the host. A project whose server does
not sets `Web\Controller::$strictTransportSecurity = 'max-age=31536000'`; both at once is a duplicate header scanners reject.

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
| `migrate`, `migrate/create`, `migrate/config`, `migrate/backup`, `migrate/restore` | migrations with a backup first; `--skipBackup` (the controller's `dbFile` and `upgradeFile` are set through `controllerMap`) |
| `params`, `params/cookie`, `params/pepper`, `params/create`, `params/update`, `params/delete` | maintain `config/params.php` |
| `user/create`, `user/password <email>` | create an account, set a password (`--password` or `YII_USER_PASSWORD`) |
| `upgrade/passwords` | mail a reset link to every user without a password |
| `search/rebuild [models]`, `search/clear [models]` | the fulltext index, optionally limited to a comma-separated list (`search/rebuild Entry,Category`) |
| `trail/clear`, `trail/optimize`, `trail/update-models` | trail retention and class renames; `clear` takes `--sleep` |
| `user-login/clear`, `user-login/optimize`, `user-token/clear`, `user-token/optimize`, `upload/clear` | login history (`--sleep`), expired tokens, abandoned uploads |
| `redirect/clean` | delete redirect loops and shorten chains; `--hosts`, `--dryRun` |
| `maintenance/enable`, `maintenance/disable`, `maintenance` | the pre-rendered maintenance page (`runtime/maintenance.php`); `enable` takes `--redirect`, `--retry`, `--refresh`, `--statusCode`, `--viewFile` |
| `registry/push`, `registry/show` | report the installation to a version registry; `--url`, `--strict` |
| `upgrade` | the v2 → v3 upgrade steps the bundle runs itself |
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
