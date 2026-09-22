## 3.0.0 (in development)

- **A custom attribute group renders as an ordinary form row, and a group of one field as a single line.**
  `Widgets\Forms\Fields\GroupField` no longer renders a `<fieldset>` with a `<legend>` of its own: the group's
  label sits in the row's `form-label` and everything it holds in the `form-content`, like every other field, and
  the container names its inputs through `role="group"` plus `aria-labelledby` rather than a `<label for>` that
  matches none of them. The add button moved below the rows on the right and is icon-only (`plus`), so it stands
  at the size of the row buttons above it, with the count the group is bound by beside it — the new
  `CUSTOM_ATTRIBUTE_HINT_MIN_COUNT` / `_MAX_COUNT` / `_MIN_MAX_COUNT` keys. It and the remove button carry their
  label as an `aria-label` and a tooltip. A group whose item
  renders exactly one field — the field count, so a translatable attribute is not one — drops that field's own
  label, the group's naming it already, and lays the input out beside the row's buttons through `.form-action`.
  The item's buttons moved from `.custom-attribute-group-item-header` into `.custom-attribute-group-item-buttons`,
  and `.custom-attribute-group-container` is gone: the container is `.custom-attribute-group` itself, carrying
  `.custom-attribute-group-single` for the one-field layout.

- **`Widgets\Grids\Columns\Buttons\DraggableSortGridButton` is `Widgets\Buttons\DraggableSortButton`.** It is
  not a grid column button — the custom attribute group rows use it too — so it sits with the other buttons.

- **A duplicate is named after its source** (monorepo issue #219).
  `Models\Actions\DuplicateActiveRecord::prefixDuplicateName()` renders the new `COMMON_DUPLICATE_NAME` key
  over the duplicate's name — per language, in that language, for a translated attribute — and truncates the
  result to the column's length, since the prefix would otherwise push a name that already fills it past its
  own string rule. An attribute the caller assigned is left alone: the action keeps the given attributes in
  `$attributes` for that. Nothing calls it by default; the entry and file actions do.

- **The language picker names its languages rather than drawing their flags** (monorepo issue #216). The navbar
  button carries the current language's two letter code — `I18N::getLanguageCode()` takes the language as an
  optional argument now — with the native language name as its `aria-label`, and the dropdown option is the
  native name alone. `Widgets\Icon::ICON_COLLECTION_FLAG` is gone with the `.i18n-icon` and
  `.i18n-dropdown-option` rules and the four flag images: a flag names a country, not a language, and the set
  only ever covered the four shipped languages.

- **`yii message` keeps the keys nothing calls** (monorepo issue #211). `messages/config.php` gains
  `keepMessages`, a list of keys per category the run adds to whatever it extracted: a permission's description
  is a `Message` pointer inside the SQL string its migration seeds, so no call site names it and `removeUnused`
  deleted every `AUTH_*_DESCRIPTION` on each regeneration — after which the permission list rendered the raw
  keys in every language. A bundle adding a permission adds its key there.

- **The colour scheme is picked in the account settings and nowhere else** (monorepo issue #210).
  `Modules\Admin\Widgets\Buttons\ColorSchemeDropdownButton` is gone from the navbar and from the bundle, with
  `includes/colorScheme.ts` and the `admin/account/color-scheme` route it posted to. The device override goes with
  it: `Modules\Admin\Module::$colorSchemeCookieName`, `$colorSchemeCookieSecure`, `getCookieColorScheme()`,
  `getColorSchemeCookie()` and `removeColorSchemeCookie()` are removed, and `getColorScheme()` now answers the
  account's `color_scheme` column alone — so a guest follows `prefers-color-scheme` and a stale `_theme` cookie is
  ignored. The account form is unchanged and still asks for a full document load when the save changed the scheme.

- **The pin moved out of the aside and into the navbar**, where it shares a slot with the drawer toggle and the
  `md` breakpoint decides which of the two is drawn. `Modules\Admin\Widgets\Buttons\AsidePinButton` renders it,
  `NavBar::getAsidePinItem()` places it, and both it and `AsideToggleButton` extend the new
  `Buttons\AbstractAsideButton`, which is where the guest check that keeps either out of a bar with no aside
  behind it now lives — the navbar renders before the aside and cannot ask it. `AsideMenu::getHeader()` answers
  `null` and `getPinButton()` is gone with it: the skeleton's aside opens straight into its menu, and the header
  is the extension point for a theme with a logo of its own. The button is icon-only (`thumbtack` /
  `thumbtack-slash`), so its label is an `aria-label` and a tooltip `includes/aside.ts` keeps in step; the
  `ASIDE_PIN` / `ASIDE_UNPIN` keys read *Expand menu* / *Collapse menu* in all four languages, and the keys and
  class names still say pin.

- **A login loads a fresh document instead of swapping into the page it was typed into** (monorepo issue #185).
  `Web\Response::setHtmxRefresh()` is now `setHtmxReload()` and covers both ways of ending a document: a redirect
  issued after it is answered with `HX-Redirect`, a response staying where it is with `HX-Refresh`, and a request
  that is not htmx is answered as it would be anyway, so an action need not ask which it is. `Web\User::afterLogin()`
  asks for one, as `afterLogout()` already did — a swap only ever reaches `#wrap`, so the guest navbar outside it and
  the login-required error already drawn into `#flashes` both used to survive the login, the second of them for good,
  since only a success alert times out. The logout gains the redirect it always issued: it lands on the login page in
  one request rather than reloading the protected page it was on and being sent there with a second, spurious
  *You must login to view this page!*.

- **The aside's logout asks first** (monorepo issue #208). `Modules\Admin\Widgets\Navs\AccountMenu` no longer
  posts `admin/account/logout` from the nav link itself — the link opens a `Widgets\Modal` and the request
  moves onto its confirm button, so the one nav item that ends the session cannot be fired by a misclick. The
  link keeps its `.nav-link` markup with `.nav-link-icon` and `.nav-link-label`, so the collapsed rail still
  hides its label. New key `ACCOUNT_CONFIRM_LOGOUT`; a functional test reaching the logout has to go through
  the modal's button.

- **`Test\Browser` puts the superglobals back when a request ends** (monorepo issue #202) — `$_SERVER`,
  `$_COOKIE`, `$_GET`, `$_POST` and `$_REQUEST`, all of which it writes. They are process-wide while the
  application is rebuilt per test, so whatever a test's last request left in them was read by the rest of that
  test — which matters now that `Modules\Admin\Module::getCookieColorScheme()` reads `$_COOKIE` directly,
  having to. The snapshot is taken per request rather than being the process-wide one #187 introduced, so a
  value the test itself put in `$_SERVER` survives its own requests instead of being reset to whatever the first
  request in the process happened to find.

- **The aside is left out of the document when it holds nothing, and can be collapsed to its icons.**
  `Modules\Admin\Widgets\Navs\AsideMenu` renders both menus in `configure()` and answers `isVisible()` from
  what they came to, so a guest gets no `<aside>` at all — the `hidden-empty` class it carried only hid an empty
  one, which a theme rendering a logo into it (`Hirtz\Anakin`) defeated. From the `md` breakpoint up, where the
  aside is in flow and costs a laptop screen a good part of its width, the new
  `Modules\Admin\Widgets\Buttons\AsidePinButton` collapses it to a rail holding the icons alone. **Nothing
  inside the aside moves for that**: `.main` is pulled back over it with a negative margin and paints on top,
  so the rail is the part of the menu the content does not cover, and opening it moves one margin on `.main` —
  the content is the width of the rail's share of the page and grows back into it, rather than sliding off to
  the right, so nothing leaves the viewport. The rail drops its `view-transition-name` for that state, since a
  named region is painted above the root group `.main` sits in and the full-width menu would otherwise flash
  over the content on every navigation. The mobile drawer is the exception and still translates, since
  shrinking a phone viewport by the drawer's width would reflow the page into a column too narrow to read. It
  opens on hover and
  **a click inside latches it open**, so the page that click navigates to still shows the menu and a submenu
  item is reachable; the next click outside, or Escape, closes it. The pin state is a host-only `_aside` cookie
  plus `data-aside-collapsed` on `<html>`, resolved by `Module::isAsideCollapsed()` and rendered by the layout,
  for the reasons the colour scheme entry below gives; the latch is `data-aside-open` beside it, written by
  `includes/aside.ts` and never persisted. **The mobile drawer moved onto the same mechanism**: `.main` and
  `.aside` translate themselves where `.wrap` used to carry it for the whole page, so the navbar stays put
  instead of leaving the screen with the toggle that had just opened the drawer. `.navbar` and `.aside-close`
  therefore carry z-indexes of their own (`39` and `38`, under the drawer's `40`), and a project restyling
  either keeps them in that order. **`body.has-aside` is gone**: `data-aside-open` is the aside's open state at
  every width now, so the drawer stays open across the navigation a tap on a nav item triggers, the same way
  the rail does, and a project keying anything on that class moves it to the attribute. Three things a project extending any of it should know:
  **`AsideMenu::getHeader()` is the new first child of the aside**, holding the pin button, and a theme putting
  its own logo there overrides that method rather than `renderContent()` (`AsideMenu::getContent()` is the list
  the aside renders); **`Widgets\Navs\NavItem` wraps its label in `<span class="nav-link-label">`**, which is
  what the rail fades out — a nav link built by hand, as the logout button used to be, needs `nav-link-icon`
  and `nav-link-label` on its two parts or its label bleeds into the rail; and **`--aside-open` is the state
  token** anything of a project's own inside the aside reads (`opacity: var(--aside-open, 1)`), the three ways
  of being open being spelled out in one place only.

- **The admin follows the operating system's light/dark setting, and a navbar dropdown overrides it** (monorepo
  issue #201, `docs/plans/color-scheme.md`). The value is tri-state — `auto | light | dark`, `auto` being the
  default and the absence of the other two — and lives in three places: the new `user.color_scheme` column is the
  durable one, a host-only `_theme` cookie is this device's override, and `prefers-color-scheme` answers when
  neither pins anything. `Modules\Admin\Module::getColorScheme()` resolves the three in that order and the
  layout renders `data-theme` on `<html>`, or no attribute at all for `auto`; `Module::current()` is the new
  static accessor the views reach it through, and `Modules\ModuleTrait::getModule()` now delegates to it.
  `Modules\Admin\Widgets\Buttons\ColorSchemeDropdownButton` is in the navbar beside the language picker and
  `AccountController::actionColorScheme()` persists the choice for an account, while `actionUpdate()` answers
  `HX-Refresh` when the save changed the scheme — `data-theme` sits on `<html>`, which every swap leaves
  standing, so the document would otherwise keep the old one until a full load. Three things a project should know:
  **a guest now gets a navbar**, holding that dropdown alone, because the login page is where the scheme is first
  picked; **the cookie is read straight out of `$_COOKIE`**, since it is written by a script and
  `Request::getCookies()` silently drops every cookie that fails Yii's HMAC; and **the cookie is host-only on
  purpose**, built with `new Cookie()` rather than through the container, whose definition carries a `Domain` and
  would reintroduce the twin of monorepo issue #195. The dark palette itself is not in this entry — the tokens
  still have to be written.

- **`Widgets\Buttons\AdminButton` is invisible to a guest** (monorepo issue #198). It declared no `roles`, and
  `Widgets\Traits\VisibilityTrait::isVisible()` answers `true` for an unset list — so a project rendering the cms
  `site/view.php` unchanged put a floating link to `/admin/dashboard/index`, and the environment badge beside it,
  on every public page. `configure()` now defaults the list to `Models\User::ROLE_AUTHENTICATED`, after the event
  and the `prepare()` closures so a caller narrowing it to a permission narrows it — `roles()` merges, and the
  default would otherwise have widened it back. A project wanting the old behaviour passes
  `roles([User::ROLE_ANY])`.

- **`Log\FileTarget` masks a credential variable however many times Apache prefixed it** (monorepo issue #197).
  `REDIRECT_` is added once per internal redirect, so the common two-rewrite `.htaccess` hands PHP a
  `REDIRECT_REDIRECT_HTTP_AUTHORIZATION` no literal name in `maskVars` reached and the bearer token was written
  to the log in the clear. The four names are wildcards now (`_SERVER.*HTTP_AUTHORIZATION` and friends), which
  `yii\log\Target::getContextMessage()` resolves with `StringHelper::matchWildcard()`.

- **`Web\Response` deletes the host-only twin of the session and CSRF cookies** (monorepo issue #195). A cookie's
  identity is its name *and* its scope, and PHP keeps the **first** of two the browser sends — the stale one. So a
  host-only `_session` left over from an earlier scope of the installation silently discarded every session written
  under the configured `Domain`: each request read the dead id, started a fresh one and wrote a cookie nothing read
  back, losing the admin language, the flashes and the CSRF token a form was rendered with between requests.
  `removeHostOnlyCookies()` sends the deletion beside the write, as `Web\User::removeIdentityCookie()` does for the
  auto login cookie, and only for a name the request actually carried twice — which `Web\Request::getDuplicateCookieNames()`
  reads off the raw header, `$_COOKIE` having collapsed the pair. The expired-header builder both share is the new
  `Helpers\CookieHelper::getExpiredHeader()`.

- **A click on a navbar search result closes the suggestion box** (monorepo issue #193). The result is a boosted
  link inside the search container, so its `htmx:after:swap` reached the listener that refills the box and reopened
  the popover over the page just navigated to. Only the input's own request refills it now; everything else is a
  navigation. The Enter key, which renders the full results page, closes the popover too and keeps the query.

- **The admin language is picked through `Modules\Admin\Controllers\AccountController::actionLanguage()`**
  (monorepo issue #194), a POST-only route carrying the language in its body, rather than by appending
  `Request::$languageParam` to the current URL. `Modules\Admin\Module::setLanguage()` no longer reads that
  parameter and takes no argument, and `Widgets\Buttons\LanguageDropdownButton` renders a `<button>` per
  language instead of an `<a>`. The navbar sits outside `#wrap`, so the URL it was rendered with is not the one
  the flag is clicked on — the link pointed back at whichever page the last full load had shown, and the
  parameter it carried stayed in the address bar afterwards. The answer to an htmx request is `HX-Refresh`, the
  whole document being what a language change redraws; a plain request redirects to the referrer.

- **`Log\FileTarget` stamps its lines in UTC** (monorepo issue #192). Yii writes the timestamp with `date()`,
  which answers in the process time zone, and `Models\User::findIdentity()` pins that to the account behind the
  request — so a file held one zone per user, was not in chronological order, and the admin read every line of it
  back as UTC, which is the formatter's `defaultTimeZone`. `getTime()` is overridden to write UTC, and
  `Modules\Admin\Widgets\Grids\LogGridView` renders the time below the date, both through the formatter and
  therefore in the reading account's zone. Lines an installation already holds are unchanged, so the entries
  either side of the upgrade are only comparable once the file rolls over.

- **`Modules\Admin\Widgets\Navs\NavBar` renders nothing when none of its items do** (monorepo issue #190).
  `getItems()` answers `null` for that rather than an empty `div.navbar-items`, the shape `Widgets\Navs\Nav`
  already uses — a subclass rendering the return value has to tolerate it. `Buttons\AsideToggleButton` is
  invisible to a guest, whose aside holds nothing and hides itself, which below `md` left the login page with an
  empty bordered strip across the top.

- **`Modules\Admin\Controllers\AccountController::actionTimezone()` refuses a request carrying no timezone**
  (monorepo issue #191) with a `BadRequestHttpException`, rather than assigning the empty value — which every
  validator skips, so the stored timezone was cleared and the flash still read as a success. The modal's script
  sets `hx-vals`; `hx-vars`, which it used, is gone from htmx 4.

- **`Web\Response::clear()` resets the htmx state it carries** (monorepo issue #187) — `$isHtmxRefresh` and
  `$htmxRedirectTarget`, whose default is now `Response::HTMX_REDIRECT_TARGET`. Only an application serving more
  than one request reaches `clear()`, which is why a single `setHtmxRefresh()` turned every later response of a
  functional test into an empty 200. `Test\Browser` restores `$_SERVER` from a snapshot beside it, rather than
  merging each request's server bag into whatever the previous one left.

- **A checkbox reads its tick off the record again** (monorepo issue #186).
  `Widgets\Forms\Fields\CheckboxField::checkedValue()` assigned `attributes['value']` beside its own property,
  which is the attribute `getInput()` reads the record's value from — so every field that named a checked value
  rendered `checked` whatever the record held. With that fixed, a `BooleanValidator` carrying `trueValue` /
  `falseValue` of its own works end to end: `Widgets\Forms\Fieldset` derives both from the rule, and
  `Behaviors\AttributeTypecastBehavior` leaves such an attribute uncast, since `(int)'yes'` is `0`.

- **The web application routes to `App\Controllers`, and the `@App` alias is gone** (monorepo issues #173 and
  #188). `Web\Application::$controllerNamespace` kept Yii's lowercase `app\controllers`, which Composer's
  case-sensitive PSR-4 lookup never resolves against a v3 project's `App\` prefix — so a project's own
  controller was a silent 404 until it set the property itself, while `Console\Application` had answered
  `App\Commands` all along. The path is pinned beside it, and `Base\Module` pins its own beside the controller
  namespace it derives: Yii otherwise derives the directory back from the namespace through an alias mirroring
  it, which only an installed extension ever has. That alias — `'@App' => '@root/app'`, a duplicate of `@app`
  pointing at the same directory — is therefore gone, and with it the prepended autoloader path through which
  Yii, rather than Composer, loaded a project's classes. `Helpers\NamespaceHelper::getPath()` answers a
  namespace's directory from Composer's autoloader instead, without an alias and without reading anything from
  disk; `Db\MigrationHistory` resolves its migration directories that way from a web request, and
  `Console\Controllers\MigrateController` registers the one alias Yii's private path resolver insists on, derived
  rather than configured, for the length of a migration run. See `UPGRADE.md`.

- **`Models\CustomAttributes\UrlCustomAttribute`'s rule and its field agree on what a URL is** (monorepo issue
  #182). `defaultScheme` and the `defaultScheme()` setter are gone — `<input type="url">` refuses a scheme-less
  value client-side, so the rewrite to `https://` could never be reached from a browser — and `enableIDN` is on,
  so a non-ASCII *host* validates the way a non-ASCII *path* already did. That makes `ext-intl` a requirement of
  the skeleton; see `UPGRADE.md`.

- **`user/create` and `user/password` can be scripted** (monorepo issue #175). `--name`, `--email` and
  `--password` skip the prompts, and `Console\Controllers\UserController::PASSWORD_ENV` (`YII_USER_PASSWORD`)
  takes the password out of the shell history and the process list. `readPassword()` read the terminal rather
  than stdin, so a piped password blocked forever and `--interactive=0` had nothing to fall back to. Both
  actions answer an `ExitCode` now, and the retry the create offers is asked for only where there is a terminal
  to ask — `confirm()` answers its default otherwise, which would have recursed forever.

- **Which installed packages are the platform is one answer** (monorepo issue #174):
  `Helpers\VersionHelper::isPlatformPackage()` over `VersionHelper::EXCLUDED_PACKAGES`, the default of
  `Modules\Admin\Widgets\Panels\ExtensionVersions::$excluded`, which stays and is passed to the same predicate.
  The list is the framework's own extensions and `davidhirtz/yii2-datetime-behavior`, a dependency rather than
  a bundle. A package whose `0.x` would distort an installation's **platform version** — `davidhirtz/yii2-vite`
  — is not excluded here: only the version registry computes such a version, and it keeps its own list, while
  the badges on the system page report whatever the installation runs.

- **`Base\Module` keeps a configured `viewPath`** (monorepo issue #172). `init()` assigned the derived one
  unconditionally, so the core config's `'admin' => ['viewPath' => '@app/modules/admin/views']` was dead — and
  it is gone with the fix, since a module now finds the views beside its own `src/` tree without being told.
  `getViewPathFromBasePath()` walked up until it popped a `src` segment, which a project's module class
  (`App\Modules\Admin\Module` under `app/Modules/Admin`) does not have: the loop popped every segment and
  answered a path above the filesystem root. Such a module takes `@views/<module id>` now, so a project no
  longer has to override the method.

- **A checkbox `Widgets\Forms\Fieldset` derives carries its unchecked value** (monorepo issue #171). It read
  `'0' !== $validator->falseValue ? $validator->falseValue : null`, and `yii\validators\BooleanValidator::$falseValue`
  defaults to `'0'` — so no hidden input was rendered, an unticked box posted nothing, and `Model::load()` left a
  stored `true` alone. Visible in `Models\Forms\LoginForm::$rememberMe`, which defaults to `true`: unticking it
  did nothing. A field that wants the key absent declares itself and passes `uncheckedValue(null)`.

- **The debug panels render again** (monorepo issue #184). `Web\Application` turns `yiisoft/yii2-debug` on for
  every `YII_DEBUG` request, but `yii\debug\Module::beforeAction()` empties `assetManager.bundles` — dropping
  the mapping of `JqueryAsset` onto `Assets\EmptyAssetBundle` that the skeleton relies on while `composer.json`
  only `provide`s `bower-asset/jquery`. Every panel answered 500 publishing `@bower/jquery/dist`. It is now
  `Modules\Debug\Module` that is wired up, and it restores the mapping for the debug routes alone. Installing
  the suggested `components/jquery` additionally gets the panels a real jQuery and a stock `yii\web\View`,
  without which their grid filters stay inert — `Web\View` drops the `POS_READY` scripts they register.
  `$jqueryPath` and `$jqueryFile` point the module elsewhere, `$jqueryPath = null` turns it off.

- **A `Set-Cookie` the application adds as a header no longer discards the ones PHP already queued**
  (monorepo issue #183). `yii\web\Response::sendHeaders()` sends the first value of every header name with
  PHP's `$replace`, so one `Set-Cookie` in the collection threw away the session cookie
  `session_regenerate_id()` emits on every login: the client kept the id it came with, found no session behind
  it on the next request, and every flash died with it. `Web\Response::sendHeaders()` appends them instead.

- **The logout removes the auto login cookie in every scope the installation writes** (monorepo issue #178).
  A `Domain` an installation has gained or lost — a tenant's cookie domain, an edited one — leaves a second
  `_auth` cookie of the same name in the browser that the scoped deletion never reaches, and the next request
  logs the account straight back in, indefinitely. `Web\User::removeIdentityCookie()` therefore sends the
  host-only deletion beside the configured one; `yii\web\CookieCollection` is keyed by name, so it goes out as
  a `Set-Cookie` header of its own.

- **A filled `Widgets\Forms\Fields\UploadField` renders as an input group** (monorepo issue #181), the
  filename as the `.input` and the remove button appended, the way `Widgets\Grids\Toolbars\GridSearchForm`
  prepends its own. The `.upload-file` wrapper is gone; a project styling it moves to `.input-group`.

- **An installation reports itself to the version registry** (monorepo issue #170). `./yii registry/push` posts
  what the system *Application* tab shows — `Registry\Report`, one JSON object with the application's name,
  version and commit, the installed extensions with their commits, PHP, database, Yii and the migration state —
  to `params.registryUrl`, authenticated with `params.registryKey` as a bearer token. Neither parameter set is
  not an error, and neither is a registry that is down or a key that was revoked: the command warns on stderr and
  exits 0, so a deploy can carry it as its last step; `--strict` makes both `ExitCode::UNAVAILABLE`. `--url`
  names the installation's URL for a console application whose URL manager has no `hostInfo`, and
  `registry/show` prints the report without sending it. The transport is `Registry\RegistryClient` over Guzzle,
  with `RegistryClient::$client` as the test seam. Beside it: `Db\Connection::getServerVersion()` (moved out of
  `Panels\ApplicationInfo`), `Helpers\VersionHelper::getInstalledExtensions()` (`name => {version, reference}`)
  and `Helpers\SecretKey::generate()` (moved out of `ParamsController`, which delegates to it).

  Building the registry as the first project outside the monorepo also fixed three project-level defaults: the
  migration namespace a project's own migrations are registered under is **`App\Migrations`** (the StudlyCase
  the console's `App\Commands` and `upgrade` already assumed; the lowercase `app\Migrations` applied the file
  but wrote a row Composer's PSR-4 lookup could not resolve), the **`@App` alias** is registered beside `@app`
  (Yii derives a module's controller path from it, and `./yii help` died on a project module without it), and
  the console's controller path is `@app/Commands`. A project that already applied a migration under the
  lowercase name renames the row once:
  `UPDATE migration SET version = REPLACE(version, 'app\\Migrations', 'App\\Migrations')`. The dist archive
  no longer ships `resources/tests/`.

- **A page whose model is not self-evident explains itself** (monorepo issue #163).
  `Modules\Admin\Widgets\HintAlert` renders an info alert a view passes its own text to, and every one of them
  is switched off at once by the account's new `user.show_hints` column (default on, `Models\User::showsHints()`,
  `Migrations\M260917100000ShowHints`) — so a view renders one unconditionally and never asks who is reading.
  The checkbox is `Modules\Admin\Widgets\Forms\Traits\UserActiveFormTrait::getShowHintsField()`, in the account
  settings and the admin's user form alike; it carries an explicit `uncheckedValue('0')`, since an unticked
  checkbox posts nothing and the stored `1` would survive the save. The shipped hints are on *Permissions*, a
  user's *Permissions* tab and *Redirects*.

- **A production installation reporting nowhere but its log file says so** (monorepo issue #168).
  `Modules\Admin\Widgets\SentryAlert` warns on the dashboard and the system *Application* tab while the request
  resolves to no environment — `Web\Request::getEnvironment()`, so local and staging are silent — and no enabled
  `Log\SentryTarget` is among the log targets. It is the target rather than `params.sentryDsn` that is checked,
  since a project may configure one itself; one reporting its errors some other way answers `unreported(false)`
  from an `EVENT_CONFIGURE` listener.

- **A token in the query string no longer escapes the log's masking** (monorepo issue #166). `maskVars` named
  `_GET.code`, because a confirmation or reset link carries its token there — but `logVars` also names
  `_SERVER`, and nothing masked `_SERVER.REQUEST_URI`, which is the whole request line. The token was written
  out in full beside the `'code' => '***'` that said it had been kept out.

  `Log\Traits\MaskQueryParamsTrait` adds `$maskQueryParams` (default `['code']`), which masks a named
  parameter's **value** wherever it appears rather than replacing a whole variable — the URL an entry happened
  on is most of what makes it readable. The new `Log\FileTarget` applies it to the rendered context, so one rule
  covers `REQUEST_URI`, `QUERY_STRING`, `HTTP_REFERER` and whatever else carries the same URL, and the core
  config points at it instead of `yii\log\FileTarget`. `Log\SentryTarget` applies it to `request.url` and
  `request.query_string` through a `before_send`, `send_default_pii` gating the cookies and the headers but not
  those two; a project's own `before_send` is composed with it rather than replacing it, as `tags` already was.

- **A `sentryDsn` parameter reports to Sentry beside the file log** (monorepo issue #164). `Log\SentryTarget`
  is added to `components.log.targets` under the key `sentry` by `Base\Traits\ApplicationTrait::preInitInternal()`
  when `config/params.php` carries a `sentryDsn`, and nothing at all is built when it does not — `sentry/sentry`
  is a dependency of this bundle now, not an integration a project wires itself. The file target the core config
  has always carried is keyed `file` rather than `0`, so a project can reconfigure either by name.

  What the default target sends is deliberately narrower than what Sentry's own `\Sentry\init()` would: its
  three error-listener integrations are filtered out, because Yii's error handler already reports through this
  target and Sentry's would both duplicate every report and take over the handler that renders the error page;
  `max_request_body_size` is `none` and `send_default_pii` is `false`, the posted body being what the file
  target's `maskVars` exists to keep out of a log that never leaves this server; `logVars` is empty, since
  `yii\log\Target::collect()` appends that dump as a message of its own; and the user is named by id alone.
  `levels` is `['error', 'warning']` and `except` is `['yii\web\HttpException:4*']` — a 404 is not a report,
  the 5xx above it is, which is where this differs from the file target's blanket `yii\web\HttpException:*`.
  `environment` and `release` resolve in three steps — the target's property, then Sentry's own
  `SENTRY_ENVIRONMENT` / `SENTRY_RELEASE` variable, then `YII_ENV` and `<root package>@<commit>`. The middle
  step is the point: naming either key at all shadows the SDK's resolution of it, and a deploy pipeline setting
  `SENTRY_RELEASE` is the only thing that can associate commits with a release. `$clientOptions` overrides any
  of it, `tags` excepted.

  **Every event carries a `project` and a `project_version` tag**, from Composer's root package
  (`Helpers\VersionHelper::getApplicationName()` / `getApplicationVersion()`) through `getTags()`. That is the
  one thing a report cannot be read without and nothing else in it carries: the frames point into the same
  bundle repositories whichever installation raised them, and `server_name` is the host rather than the project.
  A project whose `composer.json` declares no `name` reports `__root__`, which is the sign to give it one.
  `tags` is the one client option merged rather than replaced, so a project adding its own keeps these.

  The level is set on the **scope** rather than on the capture, because `captureException()` names none of its
  own and Sentry defaults a level-less event to `error` — so an exception logged as a warning used to arrive as
  an error.

- **A flash encodes what it is handed and trusts only a `Stringable`** (monorepo issue #160). A flash is
  rendered as HTML — `Widgets\Alert::content()` is the raw setter — and `Web\Controller::error()` passed a
  model's validation messages through verbatim, several of which interpolate the value the user typed
  (Yii's `UniqueValidator` is `'{attribute} "{value}" has already been taken.'`). `error()`, `success()`,
  `warning()` and `errorOrSuccess()` take `string|Stringable` now and go through the new `addFlash()`, which
  `Html::encode()`s a string and casts a `Stringable` — the same distinction `Html\Traits\TagContentTrait`
  draws between `text()` and `content()`. **A caller flashing markup hands over something that renders itself**,
  the session holding a string either way. `success()` given a model that still has errors flashes nothing,
  where it used to put the object itself into the session.

- **A grid with nothing in it renders no toolbar** (monorepo issue #159): an empty table has nothing to search
  or filter. `Widgets\Grids\GridView::isFiltered()` is what keeps it where the toolbar is what emptied the grid
  — a fruitless search must not take away the box that would clear it — and reads the grid's own
  `GridSearch` plus the request parameter of every `Toolbars\FilterDropdown` in the header, which answers
  `getParamName()` for it now. A grid wanting its toolbar regardless overrides `getHeader()`.

- **htmx 4** (monorepo issue #154). The npm release is tagged `next` rather than `latest`; `htmx-ext-head-support`
  is dropped for the `hx-head` extension htmx 4 ships inside its own package, imported into `admin.ts` after
  htmx itself, which is what assigns the global the extension reaches for.

  **Every htmx attribute written for the elements inside a container now carries `:inherited`** — nothing
  inherits by default. `Widgets\Grids\GridView::$headerAttributes` and `$tableHeaderAttributes`,
  `Widgets\Forms\Footers\FormFooter`, `Widgets\Grids\Pagers\LinkPager::$options` and
  `Modules\Admin\Widgets\Navs\NavBarSearch` are the ones the skeleton owns; a project widget putting `hx-*` on a
  wrapper has to do the same or its links swap the whole page. **An empty attribute cancels an inherited one**,
  where htmx 2 needed the literal `unset`. `show:window:top` is `show:top`, `window` no longer being a scroll
  target.

  **`#wrap` carries `hx-select-oob:inherited` now, not the body**: an out-of-band selection removes the matched
  element from the response, and the history restore — which re-fetches and morphs the whole `<body>`, there
  being no localStorage cache any more — deleted `#flashes` from the page on the first back button.

  **`includes/onLoad.ts` replaces every direct `htmx.onLoad()` call.** htmx runs its first pass on a timer that
  fires between two deferred module scripts, so on a page loading more than one entry point everything the later
  one registered was inert; the new module registers once from the shared chunk and replays that pass for
  whoever arrives after.

  The events are renamed and fire on the requesting element rather than the swapped one, so
  `includes/search.ts` and `includes/autocomplete.ts` listen on their container; the autocomplete rewrites the
  request's `FormData` rather than a `parameters` object. `htmx:confirm` fires only for `hx-confirm` now, so the
  TinyMCE flush is a capture-phase listener on `submit`, `click` and `change`. `htmx.swap()` takes one context
  object. `htmx.config.globalViewTransitions` is `transitions`, and `historyCacheSize` and `scrollBehavior` are
  gone — the smooth-versus-instant scroll of a save is `document.documentElement.style.scrollBehavior`.

- **A navigation no longer cross-fades the whole page** (monorepo issue #158). `.aside`, `.breadcrumbs` and
  `.tabs` are view transition groups of their own beside `.header-content`, so the furniture a navigation leaves
  standing morphs in place while the content is replaced outright — cross-fading a grid into a form read as a
  smear that outlasted the click. The root's animation is kept for a swap narrower than `#wrap`, which is what
  makes a re-filtered, re-sorted or re-paged grid look like the same grid. `data-navigate` therefore has a
  fourth value, `same`, and decides `none` by the swap's target rather than by the response.

- **The bar in front of the header's subtitle is drawn by its first item**, not by the container (monorepo issue
  #156): only an item carries a `view-transition-name`, so a bar on the container stayed put while the item it
  belongs to slid past it. The dot between items and the bar are one rule at two widths now, and
  `Widgets\Navs\Header::getSubtitle()` wraps a plain string subtitle in a `.header-subtitle-item` so both shapes
  render the same.

- **`Validators\Interfaces\AttributeTypeInterface` lets a validator name the type its attributes hold.**
  `Behaviors\AttributeTypecastBehavior::detectAttributeTypes()` recognised four validator classes and nothing
  else, so an attribute guarded by a validator of a bundle's own kept the string a form posted —
  `Db\ActiveRecord::load()` typecasts, but only what the behavior knows about, and a form reload never validates.
  The `data-form-target` script of `resources/assets/src/js/includes/forms.ts` is **deleted** with its last
  caller: a select that decides what the rest of the form shows calls
  `Widgets\Forms\Fields\Field::reloadsForm()` instead of carrying a value per option.

- **`Models\Interfaces\AdminModelInterface` answers for the admin's nesting** — `getAdminParent()` names the
  record this one is filed under, `getAdminIndexBreadcrumb()` the listing it appears in, and
  `getAdminSubtitle()` how a record *edited through* another names itself under that record's title. All three
  default to `null` in `Models\Traits\AdminModelTrait`, which also carries the `getAdminPositionLabel()` a
  subordinate model answers the third with — its noun and its position, as `COMMON_MODEL_ID`. That helper takes
  an optional noun, for a record whose type name would repeat what the subtitle already says before it.

- **`Widgets\Navs\ModelHeader` is the one thing that walks that chain.** The H1 stays on the **base** record,
  the first one up the chain that answers no subtitle, and every record between it and the page's own becomes
  the subtitle beneath — so a hotspot's asset reads "About — Section #3 · Asset #1 · Hotspot #2 · Asset #1"
  rather than retitling the page. A record filed under one of its own kind answers no subtitle, so an entry
  under an entry is still its own base. The breadcrumb bar gets the complete alternating chain, each ancestor's
  listing then the ancestor, uncapped and never the record itself. No header knows another header's class, and
  the hand-written breadcrumb code of `Modules\Admin\Widgets\Navs\UserHeader` and `RedirectHeader` is gone.

  **The subtitle is markup, not a joined string**: each record is an `.header-subtitle-item` linking to its own
  page where it has one, and the dot between them is a CSS rule rather than a character — the same shape as the
  bar `.header-subtitle::before` already drew, at `.25rem` instead of `1.25rem`, the two declarations merged. A
  subclass that sets `$subtitle` itself still wins and still renders as plain text.

- **The header's subtitle shows at every width and its subheading clamps to one line.** `.header-subtitle` had
  been hidden below `sm`, which now hid the only thing naming the record; `.header-title` wraps instead, so the
  subtitle drops below the title on a narrow screen. The subheading is `.header-subheading` and truncates with
  an ellipsis rather than wrapping a long permalink over several lines, which `min-width: 0` on
  `.header-content` is what makes possible.

- **`Models\Interfaces\AdminModelInterface::getPermissionName()` is the permission guarding a model's admin
  page.** It was declared ad hoc on the `Media\Models\Asset` and `Cms\Models\EntryRelation` families and
  nowhere else, so anything holding an `AdminModelInterface` had to duck-type its way to it — the cms
  `Widgets\AdminLink` asked `method_exists()` and answered `false` otherwise, which is why the frontend overlay
  link had silently stopped rendering on sections, entries and categories. `Models\Traits\AdminModelTrait`
  deliberately does not implement it, for the same reason it leaves `getAdminRoute()` to the model: only the
  model knows, and a silent default would hide every link to it or show one it should not. **Every model
  implementing the interface has to answer it** — `Models\Redirect` and `Models\User` return their own
  `AUTH_*` constant here, and a model only ever edited through another returns that one's.

- **`Widgets\AdminLink` moved here from `yii2-cms`** (`Hirtz\Cms\Widgets\AdminLink` →
  `Hirtz\Skeleton\Widgets\AdminLink`). It is the frontend counterpart of `Widgets\Buttons\AdminButton` and
  never referenced a cms class; the `.admin` class it renders is the button's own CSS, so only the consumer had
  been in the wrong bundle. **Its default class is `admin`, not `admin overlay`** — `overlay` was never a
  platform class, each project defined one — and `AdminButton::registerCss()` gives `.admin` the
  `position: absolute` / `inset` geometry that class used to carry. A caller passing its own `class` replaces
  the default, so that class owns the geometry; the positioned ancestor is still the project's markup.

- **The navbar search opens as an overlay below `md`.** The bar is in flow there and the input had nothing to
  grow into, so an open box pushed the language dropdown and the aside toggle off the edge; it now covers them
  instead, and `Modules\Admin\Widgets\Navs\NavBarSearch::getToggle()` renders a search *and* a close icon
  (`navbar-search-toggle-open` / `-close`) of which the open state picks one, plus `aria-expanded` the script
  keeps in sync. The results page no longer reopens the box where that would leave the menu behind it — the
  script reads the breakpoint off the `--navbar-search-overlay` custom property rather than repeating it.

- **`Widgets\Grids\Traits\SelectionTrait` is the grid selection**, extracted from the four grids that had
  written it out one by one — `Modules\Admin\Widgets\Grids\RedirectGridView` here, the cms
  `SectionGridView` and `BlockSectionGridView`, the media `AssetGridView` and `FileGridView`. It carries
  `$showSelection` (on by default), the checkbox column, the sticky footer the selection reveals and the
  delete button in it, and a using class calls `configureSelection()` from its own `configure()` before it
  builds its columns — a trait has nowhere to register a listener — and names the checkbox column itself.
  What a grid still answers for is `getDeleteSelectionLabel()` and `getDeleteSelectionRoute()`, both abstract,
  plus optionally `canDeleteSelection()` (default `true`), `getDeleteSelectionMessage()` (default
  `COMMON_CONFIRM_DELETE_SELECTED`) and `getSelectionItems()`, which is what lets `FileGridView` keep its move
  button beside the delete one. Renamed with it: `RedirectGridView::getSelectionButton()` and
  `SectionGridView::getSelectionButton()` are `getDeleteSelectionButton()`, and the dead
  `data-id="check-button"` attribute the redirect grid's trigger carried is gone.

- **`Db\Connection::$lockWaitTimeout` (60 s) bounds how long a restore waits for a metadata lock.** A dump
  opens with a `DROP TABLE IF EXISTS` per table, and any other connection that has merely *read* one inside an
  open transaction holds a shared lock on it — so under the server's own `lock_wait_timeout`, a day on MariaDB,
  a blocked restore hung rather than failing, and killing it left the database half dropped. The value rides on
  the `mysql` session as an `--init-command`; `0` removes the ceiling. The backup cannot carry it, MariaDB's
  `mysqldump` takes no `--init-command`.

- **`Test\Fixtures\ActiveFixture::resetTable()` deletes only the rows the fixture loaded.** Yii empties the
  whole table, which the test transaction normally rolls back — but a test that loses its transaction to DDL
  committed that delete, removing rows nothing in the suite had written. A fixture holding no loaded rows still
  clears the table, which is what the unload preceding every load is for.

- **`Console\Controllers\MigrateController` refuses to migrate a database it cannot read the history of, and
  repairs it when the project ships a repair.** v3 renamed every migration namespace, so a v2 database's
  `migration` rows name classes that no longer load — and Yii would treat every migration as new and build the
  schema again over populated tables. `Db\MigrationHistory::getUnresolved()` is the detection, and
  `MigrationAlert` reports the same condition on the dashboard. If `$upgradeFile` (`@root/upgrade/collapse.php`
  by default) exists, the controller backs the database up, runs it and reads the history again, so an upgrade
  deployment is an ordinary `./yii migrate` rather than a bespoke extra step; set it to an empty string to
  refuse instead. The file's presence is the authorisation — a deployment cannot be asked to confirm anything.
  The repair runs in `beforeAction()` because the list of new migrations is computed inside the action, and it
  takes the backup itself because `migrateUp()` would otherwise take one of an already-rewritten database.
  **Only `up`, `down`, `to`, `redo` and `fresh` are guarded**: refusing `backup` because the history is
  unresolved is backwards — it is the one thing worth doing first — and `repairHistory()` calls
  `actionBackup()` itself, so guarding it would recurse.

- **`Db\MigrationHistory` re-reads the schema rather than trusting the cache.** The schema cache outlives the
  process, so a database recreated behind a warm one passed the "does `migration` exist" check and then failed
  the `SELECT` with a 1146 — which is exactly the state an interrupted `migrate/restore` leaves, so the
  recovery path was the one that broke and `runtime/cache` had to be cleared by hand. The table schema is read
  with `refresh: true` and the result memoised, so it costs one query per instance.

- **`Models\Interfaces\AdminModelInterface` declares `getParamName()`**, implemented by
  `Models\Traits\AdminModelTrait`, so a widget can build the routes of a controller scoped to a model without
  knowing which model it has. It moved up from the media bundle's `AssetModelInterface`, where a second
  polymorphic relation needed the same thing. A model implementing the interface without the trait has to
  declare it.

- **A redirect no longer decides the htmx swap for the element that asked for it** (monorepo issue #135).
  `Web\Response::redirect()` answered every htmx request with `HX-Location`, which carries its own swap context
  and therefore overrode the `hx-select`, `hx-swap` and `hx-select-oob` of whatever issued the request — so the
  grid-only swap of `Widgets\Grids\Columns\StatusIconColumn` had never worked and a status toggle threw the user
  back to the top of the page. `setHtmxRedirectTarget()` takes `?string` now, and `null` answers with an ordinary
  redirect the requesting element follows itself, leaving all three of its attributes to apply.
  `Web\Traits\StatusControllerTrait::updateStatus()` passes it. Which of the two a redirect is only the action
  knows — a form targets itself so its validation errors land in place, and still navigates away once it saves.

- `Widgets\Buttons\Traits\AjaxAttributesTrait::replace()` sets `hx-swap` to `outerHTML` beside the target it
  already set, so it beats the body's `show:top`, and takes an optional `$selectOob` for what the response
  refreshes besides the target — prepending the flash container, which naming one of your own would otherwise
  replace. `StatusIconColumn` uses it in place of writing the attributes itself.

- **`Test\TestCase` raises `error_reporting()` to `E_ALL` for the duration of a test**, so a deprecation, warning
  or notice is an `ErrorException` and a test error — exactly what it is in a browser (monorepo issue #129).
  PHPUnit deliberately lowers the mask, because its own handler is called whatever it says; the application's
  handler is not written that way and *replaces* PHPUnit's, so every one of these was silently dropped. **A
  project upgrading will see its own suite report code it thought was covered.** Two shapes account for most of
  it: a builtin whose `false` the code checks itself (`fopen()`, `filemtime()`, `file_put_contents()`) needs `@`
  or the check is unreachable, and a `null` array offset is no longer a shrug.

- `Web\View::getFilenameWithVersion()` answers `/<filename>` with no query string for a file that is not on
  disk, where it used to call `filemtime()` unguarded and take the page down with a 500.

- `Web\ChunkedUploadedFile` reports `UPLOAD_ERR_CANT_WRITE` for a chunk it cannot read or append, which the
  unsuppressed `fopen()` in front of that check had made unreachable.

- `Widgets\Navs\Breadcrumbs` renders outside a controller — a widget test, or anything rendering before one is
  set — instead of reading `module` off a null.

- **A trait and its using class must never both declare the same `@property`** (monorepo issue #125). PHPStan
  keeps the first tag and, where the two disagree, drops that class's whole PHPDoc scope with no error of its
  own — which was the entire pre-existing level-7 baseline, reported against
  `Models\Traits\TypeAttributeTrait` for classes that only *use* it. So
  `Models\Traits\UpdatedByUserTrait` owns `updated_by_user_id` and the models no longer repeat it,
  `MaterializedTreeTrait` no longer declares `$position`, and
  `Modules\Admin\Widgets\Forms\Traits\AssetFieldsTrait` (media) no longer declares `$model`. A relation trait
  declares only its `@property-read` relation and leaves the foreign key to the model, whose nullability it
  cannot know.

  **`Widgets\Grids\Columns\LinkColumn::$url` takes the loose `Closure(mixed, …)`**, the shape
  `Widgets\Grids\Columns\DataColumn::$value` already documents: the closure a caller hands `url()` is typed
  against whichever model it re-binds the column to, which the column cannot hold. Its docblock had carried two
  `@var` tags, and repairing that surfaced five variance errors it had been hiding.

- **The status icon in a grid cycles the record's status on a click** (monorepo issue #121).
  `Widgets\Grids\Columns\StatusIconColumn::enableUpdate()` turns the icon into a button posting to a `status`
  action, and `Widgets\Grids\GridView::$enableStatusUpdate` is the public switch a project turns the feature off
  with for one grid where it is too risky — `[TenantGridView::class => ['enableStatusUpdate' => false]]`. The
  column stays an icon wherever a click must not change anything, and the grid answers for its own pickers and
  permissions:

  ```php
  protected function getStatusColumn(): ?Column
  {
      return StatusIconColumn::make()
          ->enableUpdate($this->enableStatusUpdate && $this->webuser->can(Foo::AUTH_FOO));
  }
  ```

  **`Models\Interfaces\StatusAttributeInterface` gained `getNextStatus()` and `isStatusUpdatable()`**, both
  implemented by `Models\Traits\StatusAttributeTrait` — a model implementing the interface without the trait has
  to add them. `getNextStatus()` is the next declared status, wrapping at the end of the list, and `null` where
  the model declares fewer than two or the record's stored value is not among them, so a record carrying a status
  the configuration has since dropped is left alone rather than silently moved to the first one.
  `Models\User::isStatusUpdatable()` refuses for the site owner, whose star is not a status.

  **`Web\Traits\StatusControllerTrait::updateStatus()` is the body of the action**, with no opinion on who may
  run it: a controller resolves and authorises the record the way its update action does, adds `status` to its
  access rule and to `VerbFilter` as `['post']`, and calls it. `Modules\Admin\Controllers\UserController` is the
  one here. The button derives its route by swapping `update` for `status` in the record's own
  `AdminModelInterface::getAdminRoute()`, so a model whose route falls back to an index — `Models\User` with no
  id, the cms `Category`, the media `Folder` — carries no key to act on and keeps the plain icon.

- **`Widgets\Grids\GridView::emptyMessage()` says what a grid is *for*, in place of the bare "no records"
  summary** (monorepo issue #119). `Widgets\Grids\GridSummary` renders it only while the grid is empty **and**
  nothing was searched for: a fruitless search still gets the search summary, or the user is told the grid is
  empty when it is their keyword that matched nothing. A grid whose layout drops `{summary}` when it is filled
  keeps the explanation by putting it back and hiding the summary itself, the way
  `Media\Modules\Admin\Widgets\Grids\AssetGridView` does:

  ```php
  protected function getSummary(): ?GridSummary
  {
      return parent::getSummary()
          ->emptyMessage(Yii::t('app', 'FOO_GRID_SUMMARY_EMPTY'))
          ->visible(fn (): bool => $this->provider->getCount() === 0);
  }
  ```

  `GridSummary::message()` is unchanged and still replaces the summary unconditionally.

- **A record built from a type the caller already knows goes through
  `Models\Traits\TypeAttributeTrait::instantiateByType()`, never `create()`** (monorepo issue #105). It resolves
  the class the type names through `Models\Types\Type::getModelClass()`, so building the record any other way and
  assigning `type` afterwards silently hands back the base class — with the base class's definitions, rules and
  lifecycle hooks. Nothing catches it, since the wrong class is still a valid instance of the right base.

  **`Behaviors\TrailBehavior::createTrail()` therefore takes the type**, `createTrail(int $type)`, rather than
  having it assigned to the returned trail; an override has to follow. `Models\Trail::createOrderTrail()`,
  `Models\User::afterPasswordChange()`, `Rbac\DbManager::createTrail()` and
  `Modules\Admin\Controllers\RedirectController::actionCreate()` were the other call sites here.

- **`TypeAttributeTrait::instantiateFromPost(array $data, ?int $type = null)` is what a create action builds
  with.** A type select reloads the form by posting to the same action, and `load()` cannot change the class of a
  record that already exists — so the posted type is read first and `$type`, the one the route carries, is the
  fallback for the first request. It takes the body rather than reaching for the request, the way `load()` does.

  **A consequence for `Type::modelClass()`: every class in a type family has to answer the same `formName()`.**
  It follows the runtime class otherwise, so the form would post under one name and load under another and drop
  everything typed on the switch; `Models\Asset::formName()` in `yii2-media` is what pinning it looks like.
  `instantiateFromPost()` throws an `InvalidConfigException` naming the class when the two disagree, rather than
  losing the input.

- **`Validators\HtmlValidator::$allowedClasses` also takes a `Closure` returning the array.** A class may carry a
  human-readable label as its key, which `Widgets\Forms\Fields\TinyMceField` renders as the dropdown entry — and
  a translated label is a `Yii::t()` result, which resolves before the application has an `i18n` component when it
  sits in a config file. The closure is the same shape a model's `getTypes()` takes for the same reason:

  ```php
  'allowedClasses' => fn (): array => ['a' => [Yii::t('app', 'BUTTON_PRIMARY_LABEL') => 'btn btn-primary']],
  ```

  `init()` resolves it in `setAllowedClassesByTag()` and no longer rewrites the legacy flat list into
  `$allowedClasses` — the property keeps whatever it was configured with, and `getAllowedClassesByTag()` is the
  resolved, tag-keyed value as before

- **`Widgets\Forms\ActiveForm::$rows` is `$fieldsets`, a normalized `list<Fieldset>`, and a form declares its own
  rows in `getDefaultRows()`.** Rows could be a flat list of fields, a list of groups or a list of fieldsets, and
  which one it was got sniffed off the *first* element with the answer applied to the rest. So a bare field behind a
  group was handed to a fieldset that never got its model — `Call to a member function getActiveValidators() on
  null`, one row order away — and every reader downstream had to repeat the guess: `Cms\Shopify\Bootstrap`
  re-implemented it to insert a field from another bundle, and the cms `EntryActiveForm` a second time to add its
  tenant row (monorepo issue #120).

  All three shapes still go in, and `normalizeRows()` turns them into fieldsets once, before `EVENT_CONFIGURE`
  fires. So a listener is handed `list<Fieldset>` and can reach a single fieldset instead of guessing at the form:

  ```php
  $form->rows(static function (array $fieldsets): array {
      $fieldsets[0]->rows(static fn (array $rows): array => [...$rows, MyField::make()]);
      return $fieldsets;
  });
  ```

  **A subclass overrides `getDefaultRows()` instead of assigning `$this->rows ??=` in `configure()`**, or its rows
  reach neither the normalizer nor a listener. Mixing a bare field into a list of groups is now an
  `InvalidConfigException` naming the form, rather than a fatal further down.

- **`Widgets\Forms\Fieldset::rows()` takes a `Closure`** handed the current rows, the same shape
  `Widgets\Grids\GridView::columns()` and `Widgets\Navs\Traits\ItemTrait::items()` already had, and
  `Fieldset::getRows()` reads them back. That pair is what lets another bundle place a field inside one fieldset.
  `ActiveForm::getFieldset()` is gone — `createFieldset()` replaces it and answers a `Fieldset` rather than a
  `Stringable`.

- **A type change always reloads the page, and a hidden field is gone rather than hidden.** The two halves of what a
  type decides were answered separately: `Widgets\Forms\Fields\TypeSelectField` reloaded the form only when the
  candidate types fingerprinted to different *custom attributes*, and `Models\Types\Type::hiddenFields()` was a list
  of CSS selectors a script toggled in the browser. Anything else a type decides — a field another bundle
  contributes, a panel outside the form, the cms `Models\Menus\Menu::available()` — reached neither, so two types
  declaring the same custom attributes emitted no `hx-*` at all and the type change never left the page (monorepo
  issue #118).

  The select now reloads whenever it offers more than one type, and the reload swaps the whole `#wrap` the layout
  declares rather than the form alone — the server renders the page for the posted record, so every type-dependent
  decision is answered where it is made. `Field::reloadsForm()` is unchanged as an API and is what the cms
  `TenantIdField` uses too.

  Gone with it: `Models\CustomAttributes\CustomAttribute::getFingerprint()` and the `getFingerprintData()` overrides
  of every definition class, and the `data-toggle` attribute `Widgets\Forms\Fields\SelectField` wrote with its
  `includes/forms.ts` handler. **`hiddenFields()` takes attribute names**, not selectors, and a bundle's `FIELD_*`
  marker lost the `#` it carried for that syntax.

  A hidden attribute is now dropped server-side: `CustomAttribute::isVisible()` answers `false` for one, which takes
  it out of `rules()` as well, and `Widgets\Forms\Fieldset` skips the field of a hidden column. So a hidden
  attribute is neither rendered, validated nor assigned — **and the value a record holds under a type that hides it
  survives the save** instead of being overwritten by what the form did not post. `Models\Interfaces\VisibleAttributeInterface`
  is the opt-in beside `Models\Traits\VisibleAttributeTrait`; a model using the trait has to declare it, or the
  skeleton cannot ask.

- **`Db\ActiveRecord::load()` typecasts the attributes it loaded.** A form posts strings, and only validation
  turned them back into the column's type — which a form reload never reaches, since it renders the loaded record
  and returns. So a `Closure` reading an attribute off that record saw `"2"` where the saved record holds `2`, and
  `Models\Types\Type::available()`, the cms `Models\Menus\Menu::available()` and anything else a project
  declares that way silently answered for the type the record no longer has. `Behaviors\AttributeTypecastBehavior`
  is the same one that ran before validation; it now runs after a successful `load()` too.

- **A URL import is guarded and can be turned off.** `Web\StreamUploadedFile` fetched whatever the media file form
  put in front of it, so an account holding `file` could make the server request a cloud metadata endpoint, anything
  on localhost or anything else inside the network, and read the answer back out of the error code — a value with no
  scheme at all fell through to a plain `file_get_contents('/etc/passwd')`. It now accepts `http` and `https` only,
  resolves the host and refuses a loopback, private or reserved address, follows redirects itself so every hop is
  checked again, and streams the body instead of reading it into memory whole. A refusal reports the same
  `UPLOAD_ERR_NO_FILE` as a target that answered nothing, so the code says nothing about the network.

  The policy is on the `upload` component, so an installation with no use for the feature turns it off in one line:

  ```php
  'components' => ['upload' => ['enableStreamUploads' => false]],
  ```

  Beside it, `Upload::$allowPrivateStreamUploadHosts` (`false`), `$streamUploadTimeout` (10 s),
  `$maxStreamUploadSize` (64 MB) and `$maxStreamUploadRedirects` (5) — there used to be no timeout and no ceiling,
  so a slow or endless response held a worker. `Media\…\FileImportButton` renders nothing while the feature is off.

- **`Web\CopiedUploadedFile` is the upload made from a path the application names**, and `Web\AbstractUploadedFile`
  is what the two share. A URL that arrived with a request and a file the application already holds used to be the
  same class and the same method, which is why the guard above could not simply be added: the media bundle's
  `Models\File::copy()` hands it a local path, a stream wrapper's included, and is not what any of the policy is
  about. `StreamUploadedFile` keeps `$url` and loses its `$allowedExtensions`, `saveAs()`, `getExtension()` and
  `getTemporaryUploadPath()` to the base.

- **`Models\CustomAttributes\UploadCustomAttribute` attaches one file to a record**, translatable like every other
  definition and stored by the new `upload` application component (`Upload\Upload`) rather than in the media library:
  no folder, no transformations, no assets, and it goes with the record it hangs on.

  ```php
  UploadCustomAttribute::make('track')
      ->extensions(['vtt', 'srt'])
      ->maxSize(5 * 1024 * 1024)
      ->translatable(),
  ```

  `extensions()` is required rather than defaulted: the web server serves the directory straight, so what may land
  there is an allow list — a definition declaring none throws.

  A file is picked before the record exists, so `Modules\Admin\Controllers\UploadController` parks it under a token
  in `Upload::$tempPath` and answers with the field re-rendered — the chunked upload of `Widgets\Buttons\FileUploadButton`
  unchanged, so there is no size limit to work around. The JSON column holds the filename alone; the directory is
  `<table>/<record id>/<hashed attribute>/` under `Upload::$path` (`@webroot/attachments`), so nothing in the URL names
  a column. Removing a pending upload deletes its file at once; a file the record already holds goes with the save,
  which is the only point at which the removal is more than a cleared input.

  `permission()` names the auth item the upload action requires — the skeleton cannot know a model's own, so the
  definition says it. A definition declaring none is guarded by `Upload::$uploadLimit` (120 per hour per account,
  `0` to disable), counted in the cache the way `Web\User` already counts failed logins.

- **A `Models\CustomAttributes\CustomAttribute` has a lifecycle.** `afterSave()`, `afterDelete()` and `afterDuplicate()`
  are no-ops on the base and are called by `Db\ActiveRecord` and `Models\Actions\DuplicateActiveRecord`, so a
  definition storing something outside the JSON column follows its record: the upload is moved into place once the
  record has an id, the previous file goes when the value is replaced or cleared, and a duplicate gets a copy of its
  own instead of pointing into the source's directory. `Models\Interfaces\CustomAttributeInterface` gains
  `validateCustomAttributeUpload()` beside `validateCustomAttributeGroup()`.

- **The `upload` component owns the temporary directory and its collector**, for the chunks
  `Web\ChunkedUploadedFile` assembles as much as for the files an attachment parks: one directory
  (`Upload::$tempPath`, `@runtime/uploads`), one lifetime, one switch and one place to look. `ChunkedUploadedFile`
  loses `$partialUploadPath`, `$tempFileLifetime`, `$gcProbability` and `removeAbortedFiles()` with it, and no
  longer collects from `saveAs()`; `StreamUploadedFile` loses `$temporaryUploadPath` and its setter. A download
  used to park under a path of its own, which nothing would have collected once a project moved the directory.

  `Upload::collectGarbageOncePerSession()` replaces both probability checks: a small installation has no cron to
  run `upload/clear` with, and an upload is the only moment at which the directory is known to matter, so a request
  collects at most once per session and lifetime window. `Upload::$enableGarbageCollection` turns it off.
  (monorepo issue #104)

- **`Web\Traits\UploadControllerTrait::receiveUpload()`** is the half of an upload action both bundles wrote
  themselves — read the chunk, answer `201` for one that landed and wants the next, collect what earlier uploads
  abandoned. `Media\…\FileControllerTrait` and `Modules\Admin\Controllers\UploadController` share it now.

- **`Assets\AbstractAssetBundle` registers its scripts in the head.** For a module script that changes nothing — it is
  deferred either way — but `View::endBody()` renders inside `#wrap`, so a fragment swap selecting less than that
  dropped the bundle of any widget the response introduced. A form reload
  (`Widgets\Forms\Fields\Field::reloadsForm()`) selects the form alone, so a type change that brought in a TinyMCE,
  group or upload field left it without its script; the `head-support` extension carries a head tag across every swap.

- **`Helpers\FileHelper::isFilenameTaken(string $basename, array $extensions)`** answers whether a file of that
  basename is already on disk in any of the given extensions. `Media\Models\File` uses it to keep an upload from
  overwriting a file no record knows about.

- **`Html\Traits\TagInputTrait` writes a boolean attribute as `true` rather than as an empty string**, so
  `disabled()`, `required()`, `readonly()` and `autofocus()` render the bare attribute and, more importantly, read
  back as `true`. `Widgets\Forms\Fields\Field::isDisabled()` and `isRequired()` answered `false` for a field
  disabled or required through the fluent setter, which cost such a field the exemption below and dropped it from
  the form as unsafe — `Modules\Admin\Widgets\Forms\PasswordResetActiveForm` had been rendering without its email
  input. That field is bound to `PasswordResetForm::$user` now, and
  `Modules\Admin\Controllers\AccountController::actionReset()` resolves the user before the post rather than
  beside it, so a failed validation re-renders the address and an invalid code redirects home whichever way the
  page was reached

- **`Widgets\Forms\Fieldset` no longer asks a field `isVisible()` before the field has configured itself.**
  A field is rendered like every other widget — `configure()`, then `isVisible()` — and the fieldset drops the
  rows that rendered empty, so a field may decide its visibility on what its own `configure()` resolved. The one
  check that still runs first is `!isSafe() && !isDisabled()`, because configuring a field reads its attribute
  off the model and an attribute the model does not have is a fatal rather than a missing row. That check needs
  the field's `property` before `configure()`, so **a `Field` subclass binding itself to an attribute declares
  `public ?string $property = '…';` instead of assigning it in `configure()`** — `Widgets\Forms\Fields\TypeSelectField`
  does, and `Field::isSafe()`'s `!$this->property` now means "bound to no attribute" rather than "not resolved yet"

- **`Models\Collections\TrailModelCollection::formatAttributeValue()` resolves a range attribute through
  `get<Attribute>Definitions()`**, falling back to `get<Plural>()` as `Validators\DynamicRangeValidator` and
  `Widgets\Forms\Fields\SelectField` do, and reads a `Models\Definitions\Definition` through `getName()`. It
  still read the declaration, which is indexed by offset rather than by value and now holds objects: a trail row
  recording a `type` or `status` change either showed nothing or was a fatal
  `Cannot use object of type ... as array`, taking the whole admin trail index with it. A value no definition
  matches now falls back to the value rather than to `false`

- Added `Widgets\Forms\Fields\CheckboxListField`, a checkbox per item for an attribute holding a list of
  values. The hidden input it renders in front of them is what makes "nothing checked" reach the model at all,
  and its group label carries no `for`, since it labels no single input. `itemAttributes()` sets attributes on
  an item's **label**, which is where a tooltip or a state class has to sit: a tooltip is placed against its
  element's box, and the label's is the text the reader is looking at

- **`Widgets\Flashes` no longer renders `hx-swap-oob` on its container.** htmx snapshots the page into its
  history cache verbatim, so the attribute was found again in the content a back button restores, applied to the
  live DOM and then removed from the fragment replacing it — the page was left without a `#flashes` container and
  every later response reported `htmx:oobErrorNoTarget`. The alerts are delivered by the body's
  `hx-select-oob="#flashes:beforeend"`, which needs no attribute in the response; a project that swaps a response
  in by hand has to name the flashes in its own `selectOOB`, as `components/FileUpload.ts` now does

- **A migration helper that cannot answer names the table or the item, rather than reaching Yii as `null`.**
  `Db\Traits\MigrationTrait::getTableSchema()` replaces the eight `getDb()->getSchema()->getTableSchema($table, true)`
  reads and throws for a table that is not there; `getAuthItem()` does the same for a name neither
  `getRole()` nor `getPermission()` knows, so a mistyped parent in `addPermission()` is reported instead of
  reaching `addChild()` as `null`. The fixture lookups took the same treatment —
  `Test\Traits\UserFixtureTrait::getUserFromFixture()`, `assignRole()` and `assignPermission()` name what was
  missing where they used to hand a `null` on

- `Html\Traits\TagContentTrait::addText()` skips a `null`, as `content()` and `addContent()` already did. It
  passed one to `Html::encode()`, which is a `htmlspecialchars(null)` deprecation on PHP 8.1+ for an empty
  string nothing rendered

- **PHPStan runs at level 7.** The pass cleared 1256 findings, most of them a `list<>` promised where
  `array_filter()`, `array_diff()`, `Query::column()` or a variadic collected through named arguments answers a
  key-preserving array, or a builtin's `false` reaching a non-nullable property. Two of the three live bugs it
  found are here: `Widgets\Forms\Fieldset` assigned `SelectField::make()` — an *instance* — where its other
  branches assign class strings and then called `type()` on the result, which only `InputField` has, so a
  hex-colour or dynamic-range attribute rendered through a generated fieldset was a fatal; and
  `Validators\HtmlValidator::setAllowedClasses()` iterated the configured shape rather than the normalised one,
  so the legacy flat form reached a nested `foreach`

- `Validators\HtmlValidator::getAllowedClassesByTag()` answers the tag-keyed shape `init()` settles on, where
  `$allowedClasses` still carries whichever of the two it was configured with. `Widgets\Grids\GridView` keeps
  the columns `ensureColumns()` resolved in `$visibleColumns`, and `Widgets\Forms\Fields\TinyMceField` the
  validator it built in `$htmlValidator` — the public properties stay the configuration they were given

- `Helpers\EventHelper::on()` takes the handler's event class as its fourth argument and checks it, where
  nothing verified that the handler's declared event matched the one the trigger carries

- `Test\TestMailer::getLastMessageBody()`, `getLastMessageTo()` and `getLastMessageFrom()` answer the shapes
  Symfony and Yii type loosely, so a test asserting on a mail no longer narrows them itself

- **The SAPI is stated at the access point, so `Yii::$app` can stay the `Console|Web` union.** `Web\Application::current()`
  throws for code that only ever runs under a web request — everything under `Web\`, `Modules\Admin\`, `Widgets\`,
  `Filters\` and the views — while `Web\User::current()` and `Web\Request::current()` answer `null` and are what
  shared code uses. They replace the `Yii::$app->has('user') ? … : null` idiom, which guarded at runtime but left
  the type unnarrowed, and they close four places that had no guard at all: `Html\Form::renderContent()` reached
  from a mail template, `Models\Actions\ReorderActiveRecords::runWithBodyParam()`,
  `Media\Helpers\Html::prepareLinkOptions()` and `Tenant\Models\Collections\TenantCollection::getFromRequest()`

- `Console\Application::current()` mirrors it for code that only ever runs as a command, and `Test\TestCase`
  exposes `getWebUser()`, `getWebSession()`, `getWebRequest()` and `getWebResponse()` so a test reads the
  components off the case rather than off `Yii::$app`. The five cases under `tests/Console` declare
  `$applicationClass = Console\Application::class`; three of them handed a web application to a console
  controller's constructor, which type-checked only because the union hid it

- **`Web\Request::post()` and `getBodyParams()` answer an array.** Yii answers an object for a body parser
  configured to decode into one, which `Model::load()` rejects — so every one of the 39 `load($this->request->post())`
  call sites across the bundles carried the object case. The shape is settled in one place instead

- **`Web\UrlManager::$languages` is `?array`** and is read through the new `getLanguages()`, which resolves the
  I18n default the way `Modules\Admin\Module::getLanguages()` does. The `false` the property also accepted was
  never assigned anywhere and `count(false)` would have been a fatal. `Web\View::registerHrefLangLinkTags()` now
  takes the language *identifiers* off it rather than its values, so a configured `en-US ⇒ en` produced an
  alternate URL carrying no language prefix at all

- `Widgets\Forms\ActiveForm::action()` and `rows()` no longer accept `false`: both properties are typed without
  it, so passing one was a `TypeError`, and nothing ever did

- `Helpers\Image::setImageRotation()` takes an `ImageInterface` — it returned whatever it was handed, so a string
  was a `TypeError` against its own `: ImageInterface`. `getSvgDimensions()` answers `false` for an SVG
  `simplexml_load_file()` cannot read, where it used to call a method on `false`

- `Test\Fixtures\ActiveFixture::getDb()` answers the resolved `Connection` that `yii\test\DbFixture::$db`
  still declares as a union

- **A bundle installed as the root package runs its own `Bootstrap`.** Composer never lists the root package in
  `vendor/yiisoft/extensions.php`, so a bundle tested on its own had neither its namespace alias nor the
  migrations, modules and event handlers its bootstrap registers. `Base\RootPackage` asks Composer's runtime API which
  package is the root and reads that package's `composer.json` only when it is a `yii2-extension` — a project
  pays no file read — and `Base\Traits\ApplicationTrait` adds its aliases and, where it declares one, its
  `Bootstrap`, so an installed bundle is still bootstrapped exactly once.

- **The admin logo moved out of the skeleton.** `Modules\Admin\Widgets\Navs\AsideLogo`, `NavBarLogo` and
  `Navs\Traits\LogoTrait` are gone — a theme adds its own by replacing `Navs\NavBar` and `Navs\AsideMenu`
  through the container, as `davidhirtz/yii2-anakin` does. `Navs\MainMenu` carries an `aside-main` class so an
  aside holding a logo can space itself

- `Modules\Admin\Module::ERROR_VIEW` replaces the path the module assigned to the error handler inline, which
  had gone stale (`resources/views/admin/views/dashboard/error.php`). Only a production request takes that
  branch, so nothing ever noticed

- **`bower-asset/jquery` is provided by the skeleton.** jQuery is never served — `yii\web\JqueryAsset` is replaced by
  an `EmptyAssetBundle` — so a project no longer needs the asset-packagist repository for it, and the bundle installs
  on its own. Its `composer.lock` is no longer committed; a library is tested against the newest dependencies its
  constraints allow.

- **The i18n migration helpers take strings, not a model.**
  `Db\Traits\MigrationTrait::moveI18nColumnsToTranslations()` and `restoreI18nColumnsFromTranslations()` are
  `(string $table, string $modelClass)` — building the model resolved the custom attributes it declares *today*,
  which broke a fresh install five migrations before the column those need is added. The columns are found
  without it: on the way up from the table (`getI18nColumns()`, every `<attribute>_<language>` whose source
  column is there too), on the way down from the translations themselves, so a language the installation has
  since dropped is restored rather than deleted unread. `addI18nColumns()` is gone with the model.

  The same pass fixed the `INSERT`s those migrations ran: `translation.model_class` is called `model` until
  `Migrations\M260912090000ModelClass`, which runs long after the migrations that fill the table, so a v2
  upgrade carrying a single translated column died there.

- **Nobody hands out an auth item they do not hold themselves.**
  `Modules\Admin\Controllers\UserAuthController::getAuthItem()` refuses one the acting user cannot pass — a
  `ForbiddenHttpException`, as an unmanageable target already is — and
  `Modules\Admin\Widgets\Grids\AuthItemGridView` leaves its row without a button. `authUpdate` alone was
  otherwise a takeover: grant `admin` to an account that holds nothing — one `Web\User::canManageUser()` lets
  you set a password for — and log in as it.

- **The `manager` role no longer holds `tenant`.** `Models\User::AUTH_ROLE_MANAGER` holds every permission but
  the installation-level ones, which are `system` and now `tenant` too;
  `Tenant\Migrations\M260915190000ManagerTenantPermission` takes it out of the list
  `Migrations\M260914190000ManagerRole` had handed it.

- **A language select renders the label again.** `Models\User::getLanguages()` answered
  `['de' => ['name' => 'Deutsch']]`, a shape `Widgets\Forms\Fields\SelectField` has not read since the
  definitions landed — it looks for a `label` key and fell back to the option's own value, so the account form
  offered `de` and `en-US`. Both are a plain `value => label` map now. `I18n\I18N::getLabel()` returns a
  `string` rather than `mixed` and answers the language code for one it has no label for, which the navbar's
  `Modules\Admin\Widgets\Buttons\LanguageDropdownButton` handed straight to a `string` parameter.

- **`Models\Redirect::sanitizeUrl()` is `Helpers\Url::sanitize()`.** Trimming the surrounding slashes and
  encoding the remaining whitespace is what makes two URLs comparable, which every writer of a redirect needs
  and the model was only the first to. The behaviour is unchanged; a caller renames the call.

- **`Html\Svg` renders an inline `<svg>`.** A `Base\Tag` with the content trait plus `viewBox()`, `width()`,
  `height()`, `fill()` and `xmlns()`, so an icon or a logo is composed like every other tag instead of being
  carried as a raw markup string. It renders nothing without content (`showEmpty` is `false`), and `xmlns()`
  defaults to `Svg::XML_NAMESPACE` — inline SVG inherits the namespace from the HTML document and only a
  standalone one has to declare it. **`Widgets\Buttons\AdminButton::$icon` is `string|Stringable` now**, and
  empty by default: `configure()` resolves it to `getDefaultIcon()` before the event is triggered, so a listener
  can recolour or replace the `Svg` it holds instead of rebuilding the markup. A project assigning its own icon
  markup as a string is unaffected.

- **`Helpers\EventHelper::on()` types the sender of a class-level event.** Yii's `Event::on()` cannot be
  templated — it also takes wildcard patterns — so every handler opened with an inline `@var` on
  `$event->sender`. The wrapper takes a single class string and hands the narrowed sender to the handler, with
  the event itself as the optional second argument:
  `EventHelper::on(File::class, BaseActiveRecord::EVENT_INIT, fn (File $file) => $file->attachBehavior(…))`.
  PHPStan infers the sender from the class string, so a handler declaring the wrong one is an error rather than
  a lie in a docblock. The sender is checked with `instanceof`, so an event triggered without an instance
  (`Event::trigger(Foo::class, …)`) reaches no handler registered this way. `Event::on()` stays for wildcards,
  for a handler that only reads the event, and for an event class that types `$sender` itself
  (`Cms\Models\Events\EntrySiteRelationsBuilderEvent`).

- **`Models\Definitions\Definition` takes an int backed enum.** `Definition::__construct()` accepts
  `int|BackedEnum` and unwraps it, so a project that already names a model's types or statuses in an enum
  declares them as `Type::make(EntryType::Page)` instead of repeating the `->value`. `$value` stays a plain
  `int`; a string backed enum is an `InvalidConfigException` naming the enum and the definition, since PHP has
  no `IntBackedEnum` to type against and the bare assignment would only raise a `TypeError`. The unwrapping is
  `Base\Traits\IntBackedEnumTrait`, which anything else taking a declared value uses — `Cms\Models\Sets\SectionTemplate` does.

- **`Models\Definitions\Definition` is split.** The base is what every definition shares — the value, the name,
  the icon and `make()` — and the new `Models\Definitions\ModelDefinition` adds `plural` and
  `validate(string $modelClass)`, the half only a definition a *model* declares can honour. `Models\Types\Type`
  and `Models\Statuses\Status` extend `ModelDefinition`, so every type and status subclass is unaffected, and
  `Models\Definitions\DefinitionRegistry` is bound to `ModelDefinition` — which is what keeps a definition that
  belongs to no record out of it. **A project extending `Definition` directly to declare a model's types or
  statuses extends `ModelDefinition` now.** The split exists because a definition can be worth rendering without
  a model behind it: `Cms\Models\Sets\SectionSet` is declared on a module and validates itself.

- **A type a record cannot take is refused, not merely hidden.** `Models\Types\Type::available()` was a
  presentation filter — `Widgets\Forms\Fields\TypeSelectField` left the type out of the select and nothing else
  asked — so a hand-posted value reached the database. `Validators\DynamicRangeValidator` now drops an
  unavailable type from its range, which closes it for every model that validates `type` through it.

  The exemption is the value the record is **stored** with: `Type::isAvailableOrStored()` keeps it valid and keeps
  it in the select, because a rule can stop matching records that already hold the type — a parent's type changed,
  a record moved to another tenant — and those have to keep saving and keep showing what they are. Without it the
  form also silently retyped such a record on its next save, since its own type was missing from the select.

  A project whose `available()` rule is meant to be advisory rather than binding has to widen it, most simply with
  `fn (?Model $model): bool => !$model?->getIsNewRecord() || …`. `Models\Statuses\Status` has no `available()`,
  so statuses are untouched, and so is any `get<Plural>()` returning a plain `value => label` map.

- **`Behaviors\RedirectBehavior` deletes the no-op redirect before it repoints the others.**
  `Models\Redirect::validateUrl()` resolves the chain its new target starts, so while the row the record has just
  moved back onto was still there, every other redirect updated in the same pass followed it straight back to the
  URL they were all being moved off — and stayed pointing at a URL nothing resolves.
  `Cms\Models\Actions\SavePermalinks` carried the same ordering.

- **The grid selection utility is `.block-has-selection` / `.flex-has-selection`**, keyed on
  `:has([data-check]:checked)`. It was one `.block-has-checked` keyed on `:has(:checked)`, which a **selected
  `<option>` matches as well** — a grid whose footer holds a select was therefore showing it on every page load.
  The flavours exist because `.hidden` has to be overridden by a `display` of its own, and `block` silently took
  the grid footer's `display: flex` away with its row and its gap.

- **A sticky element revealed by a `:has()` rule sticks straight away.** `includes/sticky.ts` only recomputed on
  scroll and resize, and the grid footer is measured while it is still hidden — a zero-height rect, so never
  stuck — which left it sitting below the fold until the page was scrolled. It listens for `change` now, the
  event that flips the rule. A `ResizeObserver` does not work here: it reports no box for an element that had
  none when `observe()` was called, so a `display: none` → `flex` transition delivers nothing.

- **`Behaviors\SearchBehavior::STATE_ATTRIBUTES`** is the list every searchable model reindexes on (`status`,
  `tenant_id`, `type`) and the default of `$attributes`, which a model overrides to name a column its documents
  depend on without indexing it directly. A searchable attribute that is a getter needs this: the media `File`
  indexes `filename`, which is `getFilename()`, so nothing in `changedAttributes` matched it and a rename left
  the index stale.

- **`Web\Controller::warning()`** flashes under the `warning` key, which `Widgets\Flashes` already rendered with
  an icon of its own and nothing used. It is for an outcome that succeeded but is worth saying out loud — a bulk
  file move that had to rename a file around a name collision.

- **`Widgets\Grids\Columns\CheckboxColumn` renders a name and a value.** It set neither, so the input was never
  submitted and every bulk action reading `selection` — the redirect grid's `delete-all` is the only one in the
  skeleton — received an empty selection. Its `$param` is now the bare name (`selection`), and `getName()` appends
  the `[]` for a multiple selection only, so the single-selection script, which pairs its checkboxes by name, has
  one to match. A `param()` that already carries the suffix keeps working.

- **`Widgets\Forms\Fields\AutocompleteField` and `Widgets\Forms\AutocompleteList`** are the admin's own
  autocomplete: the field renders an input with a popover below it, htmx swaps the endpoint's option list into that
  popover, and picking one writes its value into the input. It reuses the `dropdown-menu` / `dropdown-option`
  styling and the navbar search's floating-ui positioning, which both now share `includes/popover.ts`. The query
  reaches the endpoint as `q`, rewritten by `includes/autocomplete.ts` — htmx evaluates an `hx-vals` `js:` value in
  global scope, with neither `this` nor the triggering event, so the input's own name cannot be replaced there.

- **A submenu's first tab is named after its record, not "General".** Every one of them reads
  `Models\Interfaces\AdminModelInterface::getAdminType()` now — the record's type name where it has types, its
  own noun otherwise — so an entry's tab reads "Page" rather than "General" and the tab says what the page
  below it edits. `COMMON_GENERAL` is gone from the message files with its last call site; a project that
  translated it drops the key.

- **The navbar search is its own widget**, `Modules\Admin\Widgets\Navs\NavBarSearch`, so a project replaces it
  through the container instead of subclassing the whole navbar. `NavBar::SEARCH_RESULTS_ID` is
  `NavBarSearch::RESULTS_ID`, and a guest or a disabled `Modules\Admin\Module::$enableSearch` now hides the widget
  through `isVisible()` rather than a `null` from `NavBar::getSearchItem()`, which still exists and returns it.

- **`Db\Traits\MigrationTrait::moveCustomAttributesColumn()`** reorders the JSON column, and
  `moveCustomAttributesColumnToEnd()` is the way back. `Migrations\M260915130000CustomAttributesColumn` puts
  `user.custom_attributes` after `two_factor_secret` — cosmetic, so the column reads as part of the record rather
  than as an afterthought behind the timestamps.

- **`Models\Traits\VisibleAttributeTrait::getVisibleAttribute()` answers `false` for an attribute the model does
  not declare**, instead of throwing through the magic getter. A custom attribute is declared per type and per
  subclass, so "hidden" and "not there at all" are the same answer to a renderer.

- **`Db\Traits\MigrationTrait` moves columns into a `custom_attributes` column.**
  `moveColumnsToCustomAttributes()` copies a column and any `<column>_<language>` beside it into the JSON under
  its own name, then the `Models\Translation` rows of the same attributes under their suffixed one, asserts that
  every value arrived and drops the columns; `restoreColumnsFromCustomAttributes()` is the way back.

- **`Models\Traits\TranslatableAttributesTrait`** carries the `translatableAttributes` property and
  `isTranslatableAttribute()` that the media `Models\Asset` declared itself, so any model with default custom
  attribute definitions can name which of them are stored per language.

- **`Widgets\Forms\Traits\CustomAttributeFieldsTrait::getCustomAttributeFields()` takes an `except` list**, for
  a form that renders one definition in a place of its own.

- **A searchable attribute the record cannot read is skipped.** `Models\Traits\SearchableTrait` read it through
  the magic getter, which throws for a custom attribute only some types or some projects declare, so
  `getSearchAttributes()` can name an optional one.

- **`DashboardController::addRoles()` accepts a `Closure`.** A bootstrap runs on every request and the dashboard
  on one action, so naming a permission constant in the argument autoloaded the model it lives on — every model
  of every installed bundle, under the console SAPI too. Pass a closure instead:

  ```php
  DashboardController::addRoles(static fn (): array => [Entry::AUTH_ENTRY]);
  ```

  An array is still accepted and behaves as before. In this monorepo the change took the console application from
  410 declared classes to 383.

- **`getTypes()` and `getStatuses()` are instance methods, and an installation can declare them in the
  container.** The declaration has exactly one caller — `Models\Definitions\DefinitionRegistry`, which resolves
  it once per model class, per language, per application — so `static` bought nothing and cost the ability to
  configure it. Everything that reads the result (`getTypeDefinitions()`, `findType()`, `instantiate()`,
  `getStatusDefinitions()`, `findStatus()`) is unchanged and still static. A small project no longer subclasses a
  model just to name its types:

  ```php
  'container' => [
      'definitions' => [
          Entry::class => [
              'types' => fn (): array => [
                  EntryType::make(Entry::TYPE_DEFAULT)->name(Yii::t('app', 'Page')),
              ],
          ],
      ],
  ],
  ```

  The value is a **closure** because a type's name is a `Yii::t()` result and a literal in a configuration file
  would resolve before the application has an `i18n` component; a plain list is accepted where nothing needs
  translating. A class that declares its own `getTypes()` owns them and ignores the configuration.
  `TypeAttributeTrait::setTypes()` and `StatusAttributeTrait::setStatuses()` are what `Yii::configure()` writes
  through — a public `$types` property would have shadowed the `getTypes()` magic property. **Breaking:** drop
  `static` from every `getTypes()` / `getStatuses()` override, see `UPGRADE.md`.

- **`Models\User::AUTH_ROLE_MANAGER` is a new role, and a role lists permissions rather than other roles.**
  `Migrations\M260914190000ManagerRole` creates `manager` with every permission the installation has and flattens
  `admin` to the same list, in place of the `author` and `media` roles it used to group them under; those are
  detached by the bundles that own them. Neither role inherits the other, so an `AccessRule` an administrator
  should also pass names both, and a new permission is added to both:
  `addPermission($name, $description, User::AUTH_ROLE_ADMIN, User::AUTH_ROLE_MANAGER)`.
  `Test\Traits\UserFixtureTrait::assignManagerRole()` is the counterpart of `assignAdminRole()`.

- **`Modules\Admin\Module::AUTH_SYSTEM` (`system`) is what an administrator holds and a manager does not.** It
  covers reading the installation rather than running it — the error logs, `phpinfo()` and the system page's
  infrastructure rows — and it is a permission rather than a role check so that the list a role shows is the whole
  of what it can do, and so a project can grant it without handing over the admin role.
  `Migrations\M260914220000SystemPermission` adds it to `admin` alone.

- **The admin system page is a manager's page, minus its infrastructure facts.**
  `Modules\Admin\Controllers\SystemController` allows both roles for every action but `php-info`, which takes
  `Module::AUTH_SYSTEM`, as `Controllers\LogController` and the error log nav item do.
  `Panels\ApplicationInfo` hides the repository name (the commit reference stays), the database name, the link to
  `php-info` and the Yii and extension rows behind the same permission, and `Panels\ServerInfo` hides the host
  name `php_uname()` reports.

- **The admin system page reports the installation, not just its caches.** It is three tabs now, behind
  `Modules\Admin\Widgets\Navs\SystemSubmenu`, one card each: *Application* (`actionIndex()`) names the
  application, its Composer version and commit reference, when it was last deployed, the environment, the database
  server, the last applied migration, PHP — linking its version to `php-info` — and the installed extensions as
  tooltip badges; *Server* (`actionServer()`) what it runs on; *Maintenance* (`actionMaintenance()`) the published
  assets, every cache component, every connection's schema cache and the sessions, each row with its own button. `Grids\AssetBundleGridView`, `Grids\CacheGridView`
  and `Grids\SessionGridView` are **removed** — none of them listed records, and the published asset directories
  are named by a `crc32` hash that cannot be mapped back to an `AssetBundle`. Both cards are
  `Widgets\Panels\InfoList`, which renders label / value rows with an optional action through the form row classes
  (`form-rows`, `form-group form-row`, `form-label`, `form-content`) rather than a table, so a row stacks at phone
  width like every other form row. It is extensible through `EVENT_CONFIGURE` and its `rows()` closure.

- **`Panels\ServerInfo` reports what the installation runs on**, on the Server tab: the server software and host
  name, the trusted hosts, the mail transport and the time zone. It **warns when a request arrived through a proxy
  while `Request::$trustedHosts` is empty** — `filterHeaders()` strips every `X-Forwarded-*` header in that case,
  so the misconfiguration is invisible from `getHeaders()` and the secure cookie flag and HSTS are silently
  skipped. The mailer row reports only the DSN's scheme, host and port, never its credentials.

- **`Modules\Admin\Widgets\DirectoryAlert` warns about a directory the installation cannot write to**, on the
  dashboard and the Server tab, beside `MigrationAlert`. `DirectoryAlert::$directories` holds the path aliases —
  `@runtime` and `@webroot/assets` by default — which a bundle extends through `EVENT_CONFIGURE` rather than the
  skeleton naming another bundle's upload path; the alert names the resolved path, since that is what has to be
  created or chmodded.

- **`Panels\ExtensionVersions` lists the installed extensions as badges**, the version in each badge's tooltip so
  the row stays one or two lines. Its `excluded` property is the block list, matched against the full package name
  with `fnmatch()`; it defaults to `yiisoft/*` — the framework's own version is a row of its own — and
  `davidhirtz/yii2-datetime-behavior`, which is a dependency rather than a bundle.

- **`Modules\Admin\Widgets\MigrationAlert` warns about migrations the database has not applied**, on the dashboard
  and the system page, the way `EnvironmentAlert` warns about a local or staging host. It reads `Db\MigrationHistory`,
  which resolves the applied and pending migrations from a web request — so the migration namespaces are collected by
  `Base\Traits\ApplicationTrait::setMigrationNamespace()` under **both** SAPIs now, into a plain property that
  `getMigrationNamespaces()` returns and `Console\Controllers\MigrateController` reads at `init()`. It used to
  register a console-only `EVENT_BEFORE_ACTION` handler, and the controller's two default namespaces moved to the
  application beside it.

- **`SystemController::actionPhpInfo()` and `actionSchema()` are reachable.** Neither was linked anywhere: php-info
  is the PHP row's own link and the schema cache refresh is the action of every connection's row. `phpinfo()`
  prints a complete document under a web SAPI, so the action renders it without the admin layout — it produced a
  document nested inside the admin page before, which no test caught because `phpinfo()` is plain text under the CLI
  SAPI. `actionSchema()` flashes its success like the other three actions.

- **`Db\DatabaseComponents` finds the configured connections**, as `Caching\CacheComponents` finds the caches, and is
  reset from `ApplicationTrait::preInitInternal()` with it. `Helpers\VersionHelper` reports the application's own
  version, commit reference and deployment time — from `.git` where it exists, since `vendor/composer/installed.php`
  is only rewritten by `composer install` — and the installed extensions.

- **The sitemap is generated by sitemap classes, not by models.** `Web\Sitemap` moved to `Sitemap\Sitemap` and its
  `models` property became `sitemaps`, holding objects implementing `Sitemap\SitemapInterface` — `generateUrls()`,
  `getPageCount()` and `getLastModified()`. `Sitemap\ModelSitemap` generates the URLs of an `ActiveRecord` through a
  `url` closure or an overridden `getRecordUrls()`, and `Sitemap\UrlSitemap` holds the component's own `urls` and
  `views`, which are now one sitemap among the others rather than a special case of the index. `Behaviors\SitemapBehavior`
  and `Models\Interfaces\SitemapInterface` are gone: a model needs neither a behavior nor an interface to appear in a
  sitemap any more. See UPGRADE.md

- **The sitemap index paged by the wrong number.** It divided the model's URL count by the *component's*
  `maxUrlCount` while the model limited its query by its own, so a sitemap overriding it lost every URL past the
  first page; and a model producing one URL per language divided the page size by the language count in floating
  point, which `QueryBuilder::hasLimit()` drops for anything but a whole number — every page then held every
  record. Both are counted in one place now: `ModelSitemap::getRecordsPerPage()` divides with `intdiv()` and
  `getPageCount()` is what the index asks.

- **`Sitemap::$variations` is normalized to a list.** A callable returning a single factor — what `Tenant\Bootstrap`
  configures — made `SitemapController::behaviors()` append to a scalar, which is a fatal on a tenant installation
  that has `useSitemapIndex` on. Read it through `getVariations()`.

- **The sitemap XML namespace was wrong.** It was `https://www.sitemaps.org/schemas/sitemap/0.9/`; the protocol
  declares `http://www.sitemaps.org/schemas/sitemap/0.9`, without the scheme's `s` and without the trailing slash.
  The image namespace is also declared up front now — XMLWriter drops an attribute written after the first child,
  so an image on anything but the first URL produced a document referencing an undeclared prefix. An unknown
  sitemap key, and an offset past the last page, answer `404` instead of an empty but valid sitemap, and a view
  file's `lastmod` is its modification time rather than its inode change time.

- **A request knows whether it is local or staging.** `Web\Request::$environments` maps an environment name to the
  host patterns that identify it, matched with `fnmatch()` against the host name — `localhost` and `*.localhost` are
  `Request::ENVIRONMENT_LOCAL`, `stage.*` and `*.stage.*` are `ENVIRONMENT_STAGE`, and a host matching neither is
  production. `getEnvironment()` answers the key, `getEnvironmentName()` its label, and three places say so: the home
  breadcrumb appends it to `Yii::$app->name`, the admin dashboard renders the new
  `Modules\Admin\Widgets\EnvironmentAlert` (which renders nothing on a production host, so a project's own
  dashboard view can echo it unconditionally), and `Widgets\Buttons\AdminButton` carries a badge — which also stops
  the button fading out, since a badge nobody sees is pointless. `AdminButton::registerCss()` takes that as its
  argument and is deduplicated by `View::registerCss()`'s own key rather than by a static flag.

- **`Helpers\ArrayHelper::simpleXmlToArray()` is gone**, replaced by `Xml\XmlNode` — a readonly node with a `name`,
  a `text`, its `attributes` and its `children`, built with `XmlNode::fromString()` or `fromElement()` and read with
  `getAttribute()`, `getChild()` and `getChildren()`. The namespace handling is unchanged, a prefixed attribute or
  child still keeps its prefix, and `toArray()` returns the array the helper did. `fromString()` throws for a
  string that is not valid XML, where the helper accepted a `?SimpleXMLElement` and then dereferenced the `null`.
  See UPGRADE.md

- **`Models\UserLogin::$type` is an integer.** It was the last string-valued type in the platform, and it kept
  `Models\Definitions\Definition::$value` a `int|string` union for one model. The five constants become
  `TYPE_LOGIN` 2, `TYPE_COOKIE` 3, `TYPE_SIGNUP` 4, `TYPE_CONFIRM_EMAIL` 5 and `TYPE_RESET_PASSWORD` 6, with
  `TYPE_OTHER` 1 — deliberately `TYPE_DEFAULT` — as the catch-all, and `Web\User::$loginType` is typed `int` with
  that default rather than the undeclared `'unknown'` it carried before. `M260914180000UserLoginType` converts
  the column to a `tinyint` and collapses everything it cannot map, which is every provider name the removed
  social login wrote; a project that wrote types of its own maps them through `params['userLoginTypes']`.
  `UserLogin::getTypeName()` and `getTypeIcon()` lose their fallbacks — `ucfirst($this->type)` and
  `"brand:$this->type"`, which rendered a Font Awesome brand icon that only existed for the social providers —
  since every value is now declared. See UPGRADE.md
- **A type value is normalized where it is read, not where it is looked up.** `findType()` takes `?int`, but a
  form posts a string and PDO answers one for an integer column, so `TypeAttributeTrait::getType()` and
  `instantiate()` go through `normalizeTypeValue()`; a narrowing `getType()` override has to call it too. The
  `int|string` signature this replaces was silently absorbing unvalidated input

- **A model's types and statuses are objects, not arrays.** `getTypes()` returns a `list<Models\Types\Type>` and
  `getStatuses()` a `list<Models\Statuses\Status>`, built fluently — `Type::make(self::TYPE_X)->name('…')->icon('…')`
  — over a shared `Models\Definitions\Definition` base. The declaration is read through
  `getTypeDefinitions()` / `getStatusDefinitions()`, `findType()` / `findStatus()` and `$model->getType()` /
  `getStatus()`, which resolve, validate and cache it in `Models\Definitions\DefinitionRegistry`, keyed by model
  class **and** application language, and reset with the application. `getTypeOptions()` is gone, an array item
  throws `InvalidConfigException` naming the model, and a duplicate value throws instead of silently winning. The
  type's integer is the constructor argument rather than the array key, so two declarations compose with the
  spread operator. `Models\Types\TrailType` carries `message()`, `parentType()` and `hasDataModel()`; the `class`
  key is `modelClass()`, validated to be a subclass of the declaring model; `hiddenFields()` is variadic; and
  `available()` is new — whether a type is offered for a record in the admin, honoured by
  `Widgets\Forms\Fields\TypeSelectField` and `Widgets\Grids\Toolbars\TypeFilterDropdown`. `getTypeInstances()`
  is cached in the registry too, which is where it gains a reset it never had. **`getPlural()` falls back to the
  name, not to `Inflector::pluralize()`** — the inflection is English-only and the plural is now what the cms
  navigation, the entry header and the type filter render, so a German `Seite` must not become `Seites`; declare
  `plural()` where the two differ. See UPGRADE.md

- **The auto login cookie is `_auth` and the session cookie is `_session`**, replacing Yii's `_identity` and
  PHP's `PHPSESSID`. Both are renamed rather than reused because the `secure` flag is derived from the request:
  a host answering on http *and* https writes both a `Secure` and a plain copy, and a browser then refuses every
  plain HTTP response the right to overwrite or delete that name (RFC 6265bis §5.4) — so a cookie left stale by
  the v3 `auth_key` rotation could not be cleared, and the user was logged out on every session lapse. A fresh
  name has no `Secure` twin, and the upgrade already invalidates every auto login cookie it would have kept.
  `Web\User::$cookieSecure` and the session's (through `Web\SessionTrait`) pin the flag instead of deriving it,
  which is what stops the trap re-arming; both default to `null`, the previous behaviour. Everyone is logged out
  once on deploy. See UPGRADE.md
- `Test\TestCase::reloadApplication()` builds a second application on the connection of the first, so the test
  keeps its open transaction and the rows it wrote. It is how a test pins what a request must not inherit from the
  one before it — the static caches a bundle's `Bootstrap` clears
- **`./yii redirect/clean` removes the redirects that can never resolve.** One whose target comes back to its own
  request URI and the members of a cycle are deleted one by one so the trail records them; a target that is
  itself redirected is shortened to the end of its chain, and a redirect merely *leading* into a cycle is
  reported and left alone. It follows a target exactly as `Web\ErrorHandler` does, which needs to know which
  hosts a `request_uri` may be qualified by: `--hosts` takes them, defaulting to the URL manager's, and the
  command says so when it has none. `--dryRun` reports without writing. Whether a target still resolves is
  never asked — a redirect may point at a static file or another site, and guessing would delete good rows
- **A rename back to an earlier URL no longer builds a redirect loop.** `Models\Redirect::validateUrl()` ran its
  self-check *before* it flattened a chain and never re-checked the target it had just copied in, so a record
  renamed back to a URL it already had resolved through the redirect the first rename left behind and landed on
  its own `request_uri` — a row that redirects a URL to itself, past the guard written to prevent exactly that.
  It now follows the chain to its end (a cycle among existing rows terminates on a visited set) and checks the
  resolved target. `Behaviors\RedirectBehavior::updatePreviousRedirectUrls()` deletes a redirect the owner has
  just moved back onto instead of updating it into a no-op, and both it and `insertRedirect()` report a failed
  save through `Yii::warning()` rather than dropping it. **The self-check only sees a row whose two columns are
  in the same shape**, so a writer storing a host-qualified `request_uri` beside a relative `url` — the cms
  entry redirects — has to keep its own rows out of a loop
- **A grid's columns and a form's rows can be contributed from outside.** `Widgets\Grids\GridView::columns()` is
  new and `Widgets\Forms\ActiveForm::rows()` also takes the `Navs\Traits\ItemTrait` closure form, so a
  `Widget::EVENT_CONFIGURE` listener is handed the current collection and returns the one it wants — a bundle
  adding a column or a field no longer needs every project to subclass the widget. `GridView::ensureColumns()`
  moved after `parent::configure()` for the same reason: a column a listener or a `prepare()` closure contributed
  still has to be bound to its grid and asked whether it is visible
- **A stale auto-login cookie is dropped instead of renewed.** `Web\User::renewIdentityCookie()` validates the
  `_identity` cookie against the current identity before extending it — Yii re-sends it unread, so a cookie whose
  auth key the database no longer holds (every one issued before a password change, a password reset or the v3
  upgrade, which rotates `auth_key` for the accounts whose v2 hash it drops) was handed another full lifetime on
  every request and only ever failed once the session lapsed, logging the user out with an
  `Invalid cookie auth key` warning. A request renewing it while a freshly issued cookie was still in flight put
  the stale value back, so the logout repeated indefinitely. A cookie naming a different user than the session is
  dropped too
- **`Web\Sitemap::$models` is the configuration, and the instantiated models live beside it.** `init()` read the
  `behaviors` key back off the model it had just created — `ActiveRecord` is an `ArrayAccess`, so the key resolved
  to the behaviors already attached and any model declaring a `sitemap` behavior of its own crashed the component.
  The key is taken off the configuration now, before `Yii::createObject()`, and still defaults to
  `Behaviors\SitemapBehavior`. `generateFileUrls()` no longer collides a `paramName` of `false` with the route's
  own key, and reads the `params` of a view entry, which it documented but dropped; a model sitemap in
  `generateIndexUrls()` carries no `lastmod`, which was always empty
- **`Modules\Admin\Module::$alias` is `params['adminAlias']`**, read through
  `Base\Traits\ApplicationTrait::getAdminAlias()` and defaulting to `admin`. The property was never read: the URL
  rules took the value from the raw `modules.admin.alias` config array, so setting it on a subclass did nothing,
  and reading it off the module would have cost an instantiation per request. Moving the admin now also closes the
  default path — `Module::beforeAction()` refuses `admin/…` reaching it through Yii's fallback route resolution
  whenever the alias differs. See UPGRADE.md
- The navbar search box closes and clears itself on every navigation but the one to the results page, which
  `includes/search.ts` recognises by the location htmx pushed before the swap. The navbar is never swapped, so
  the query of the page before used to stay in the input
- **`Modules\Admin\Module::$languages` is the admin's own language list**, defaulting to the application's
  content languages but no longer tied to them — the admin overrides whatever language the URL manager resolved,
  so the two lists are independent. A single language is pinned and hides both pickers, and the account language
  is validated against the list, so a stored language the admin no longer offers falls back instead of switching
  it into a language it has no messages for. `I18N::$sessionKey` and its two session-language methods moved to
  the module, which `Modules\ModuleTrait` now reaches the way the other bundles reach theirs. See UPGRADE.md
- **Russian and both Chinese translations are dropped**, with their flag images and `I18N::$languageLabels`
  entries: the shipped set is `de`, `en-US`, `fr` and `pt`
- **Every message file is translated.** `fr` and `pt` were placeholder files of empty strings across all nine
  bundles — which renders the raw key, since `PhpMessageSource` does not fall back to the source language — and
  the `tenant` ones held English text copied verbatim. They were filled and the remaining German gaps closed;
  German plurals use the ICU categories the language needs. The French and Portuguese were not reviewed by
  native speakers
- **`messages/config.php` declares the `categories` it owns**, and `Console\Controllers\MessageController`
  writes those and nothing else — a category it does not own is never written, and no message file is ever
  deleted. Yii deletes every file whose category a run did not produce, so `yii2-anakin`, whose `sourcePath`
  reached only `src` while its keys live in `resources/views`, lost all seven of its message files on every run.
  The shared config now scans the whole `bundles` tree (`resources` and every sibling bundle included, `tests`
  and `messages` excluded), because a bundle routinely translates through another's category and `removeUnused`
  dropped every key it could not see; it also pins `phpDocBlock`, which used to be overwritten with Yii's
  boilerplate on each run. A config without `categories` throws
- **The literal v2 strings left in the views and mail templates are keys.** `Yii::t('skeleton', 'Back to login')`
  is `ACCOUNT_BACK_TO_LOGIN`, the five `resources/mail/account` templates share `MAIL_ACCOUNT_*`, and the three
  `Yii::t('app', …)` call sites in the bundles are gone — `app` belongs to the host application, so a bundle
  writing into it left an `app.php` behind in every message directory. Dead keys the rewrite left behind were
  dropped, `COMMON_USER_LOGINS`, `REDIRECT_CREATE_TITLE` and `USER_CREATE_TITLE` had no English text and rendered
  as their own key
- **One permission per admin-managed model.** `Models\User::AUTH_USER` (`user`) replaces `AUTH_USER_CREATE`,
  `AUTH_USER_UPDATE` and `AUTH_USER_DELETE`; `Models\Redirect::AUTH_REDIRECT` (`redirect`) replaces
  `AUTH_REDIRECT_CREATE`. `AUTH_USER_ASSIGN` (`authUpdate`) and `Models\Trail::AUTH_TRAIL_INDEX` keep their verb
  and their value — the first is the escalation-capable one, the second is read-only. `Migrations\M260914100000AuthItems`
  grants the new item to every parent and assignee of any old one, so an account that held `userUpdate` alone now
  manages users outright. No `can()` call takes a record any more, except the `user` param the user permissions read
- **`Rbac\Rules\OwnerRule` is gone**, and with it the only `yii\rbac\Rule` the platform shipped and the serialized
  class in `auth_rule.data`. The policy is `Web\User::canManageUser()`, which `Web\User::can()` consults for the
  `user` param: the site owner and anyone holding a permission the acting user lacks stay unreachable, memoisation
  included. `M260914100000AuthItems` clears every `rule_name` and empties `auth_rule` — the table itself stays,
  `yii\rbac\DbManager::loadFromCache()` reads it. `Console\Controllers\UpgradeController` lost
  `updateUserUpdateRule()`, and `M231105142331OwnerRule` and `M260913150000UserDeleteRule` are no-ops
- **Added `I18n\Message`**, a `JsonSerializable` and `Stringable` pointer at a translation (`category`, `key`,
  `params`) stored in place of rendered text, so a row written by one user reads in the language of whoever looks
  at it. `auth_item.description` and the `trail.message` of an order trail hold its JSON; `Message::fromJson()`
  returns a literal message for anything that is not a pointer, so every row written before 3.0 still renders.
  `Widgets\Grids\Traits\MessageSourceTrait`, which tried to translate rendered English back by walking every
  message source, is deleted — it missed, which is why the German admin saw English descriptions
- `Db\Traits\MigrationTrait::addPermission()` creates a permission with a `Message` description under any number of
  parents; `replaceAuthItems()` collapses a set of old items into one and `restoreAuthItems()` is its reverse
- `Models\Trail::createOrderTrail()` takes a `Message` instead of a rendered string, `getMessage()` returns the
  translated text of the `message` attribute, and the `message` of a `getTypes()` entry is a `Message`, not text
  rendered in the source language. `Rbac\DbManager::createTrail()` writes the item's `name` and `type` into `data`
  and no message at all, so an assign trail renders the item's current label
- `Models\AuthItem::getLabel()` renders the description, falling back to the display name; `getTypes()` names the
  two types through `AUTH_ITEM_TYPE_ROLE` and `AUTH_ITEM_TYPE_PERMISSION` instead of untranslated English, and the
  unused `hasPermission()` is gone. `Modules\Admin\Widgets\Grids\AuthItemGridView::$prevRuleName` went with it:
  it grouped rows by the lowercase prefix of the name, which was the verb split read back out of the name
- `Modules\Admin\Controllers\Traits\UserTrait::findUser()` keeps its permission argument — the user permissions
  are the only ones with a per-record policy left
- `Models\Collections\TrailModelCollection::reset()` drops its two statics, and
  `Base\Traits\ApplicationTrait::preInitInternal()` calls it, so an application no longer starts with the records
  the one before it loaded — a renamed record showed its old name in the trail of the next test in the same process
- `messages/config.php` adds `Message::make` to `translator`, so `yii message` finds the keys that live only in a
  message pointer
- The admin language can be switched per session: `Modules\Admin\Widgets\Buttons\LanguageDropdownButton` links
  to the current URL with the `language` query parameter (`Web\Request::$languageParam`), which
  `Modules\Admin\Module::beforeAction()` validates against the configured languages and keeps in the session
  through the new `I18n\I18N::getSessionLanguage()` / `setSessionLanguage()` / `hasLanguage()` — the account's own
  language is what it falls back to, and saving the account settings drops the override again. A guest switches
  the language too, on the login page, and the pick outlives the login. The dropdown links are `hx-boost="false"`:
  the navbar renders outside `#wrap`, so a boosted swap would leave the flag and the labels in the previous
  language. **`Web\UrlManager::$i18nUrl` is the language of the frontend URL and never the
  admin's**: the dropdown appends its parameter to the plain current URL so `createUrl()` cannot turn it into a
  path prefix, and the admin keeps the account's language whatever the URL — or the tenant behind it — resolved
  to. An administrator does not have to speak the language of the tenant they are editing
- `Models\Forms\Traits\UserFormTrait::load()` reports a load when only the user was loaded. A form that renders
  no field of its own — `Models\Forms\AccountUpdateForm`, whose fields are all the user's — never passed the
  `if ($form->load(...))` of its action, so the account settings silently saved nothing
- A request that fetches or stores a file makes the page busy: `includes/busy.ts` puts `inert` on the body, an
  `.is-busy` overlay over everything and a progress bar above that — indeterminate while the length is unknown,
  fed with the byte count by the chunked upload. `components/FileUpload.ts` used to append a bar of its own, and
  only past a size threshold, while the page stayed usable and a second upload could be started behind the first.
  An htmx-driven element opts in with `data-busy`
- `Modules\Admin\Controllers\UserController::actionUpdate()` renders a user the acting one may not change —
  the site owner, or anyone holding a permission they lack — read-only instead of refusing the request, so every
  link to a user leads somewhere. `Widgets\Forms\ActiveForm::readonly()` is what that does: every fieldset is
  `disabled` and the buttons are dropped. `Models\User::isSearchResultVisible()` and
  `Modules\Admin\Widgets\Navs\UserSubmenu` no longer scope their permission check to the record either, which
  is what kept the owner out of the admin search; `Modules\Admin\Widgets\Buttons\UserDeleteButton` does check
  the record now, since the page it sits on is reachable without the permission
- The role markers `ROLE_ANY` (`*`) and `ROLE_AUTHENTICATED` (`@`) moved from `Widgets\Traits\VisibilityTrait`
  to `Models\User`, beside the `AUTH_*` permission names. A trait constant cannot be read through the trait, so
  every call site had to reach them through an arbitrary using class — `Widget::ROLE_AUTHENTICATED` in a
  controller declaring an `AccessControl` rule, which has nothing to do with widgets
- The navbar's search results carry the literal id `Modules\Admin\Widgets\Navs\NavBar::SEARCH_RESULTS_ID`
  instead of a generated one. `Helpers\Html::getId()` restarts at `i1` on every request while the navbar
  survives every htmx swap, so its generated id was also the id of an element in a later `#wrap` — and both
  `getElementById()` and `popovertarget` resolve to the first match in the document, which left action dropdowns
  on the swapped page opening nothing. Every id outside `#wrap` has to be a literal
- `Models\Actions\ReorderActiveRecords::run()` runs the whole action in one transaction, `afterReorder()`
  included: the trail it writes and the parent record it touches describe positions that were only committed
  once the reorder itself had finished, so a failure there left the two disagreeing.
  `reorderActiveRecordsInternal()` is `reorderActiveRecords()` and opens no transaction of its own
- `user.google_2fa_secret` is `two_factor_secret` (`Migrations\M260913190000TwoFactorSecret`). Every command,
  form, validator and method around it already said `TwoFactor`, and the column was the last thing naming a
  vendor that has nothing to do with TOTP. `Models\Forms\TwoFactorAuthenticatorForm::SESSION_SECRET_NAME`
  replaces the `google_2fa_secret` session key it used while setting one up
- **v2 passwords are not carried over.** `Migrations\M260913180000PasswordScheme` drops every hash that still
  carried a v2 per-user salt and rotates its auth key, so v2's five-character minimum cannot outlive the upgrade
  and every hash in a v3 database is peppered. Those accounts land where a user created without a password
  already sits — no login until the reset link is used — while their open sessions, roles and second factor are
  untouched, which is what keeps the administrator running the upgrade from locking themselves out. See
  UPGRADE.md
- `Console\Controllers\UpgradeController` adds `upgrade/passwords`, which mails a reset link to every user
  without a password. It is the other half of the migration above and deliberately not part of it: a migration
  runs in CI, on staging and on every developer's machine, and must never mail your users. Repeating it is safe,
  since it only reaches the users who still have no password. `Console\Controllers\UserController` adds
  `user/password <email>` for the administrator whose mailer is not an option
- `user.password_salt` is `password_scheme` and no longer holds a salt: bcrypt carries its own, and the column
  records which scheme a hash was written under — `Models\User::PASSWORD_PEPPER` or `null` — which is what lets
  the `passwordPepper` param be added, removed or rotated on a running installation.
  `Models\User::getSeasonedPassword()` lost its legacy branch along with the v2 hashes
- `Modules\Admin\Controllers\UserController::actionReset()` emails the reset link it creates. It used to write
  a token nobody could reach and flash that something had happened, and nothing linked to it either — the new
  `Modules\Admin\Widgets\Navs\UserPasswordResetButton` puts it in the user action dropdown. It refuses the
  request when `Web\User::$enablePasswordReset` is off, since the action the link lands on would refuse it too
- Tokens live in a `user_token` table of their own and are stored as an HMAC, never in the clear. A read of the
  `user` table used to hand over a working password reset for every account that had one. `Models\UserToken`
  holds the verification tokens, the password reset tokens and the 2FA recovery codes, one row each, with a
  per-token `expires_at` (`Migrations\M260913170000UserToken`); `Models\Queries\UserTokenQuery` finds one by
  `whereToken()`, which hashes before it compares. `user` loses `verification_token`,
  `verification_token_created_at`, `password_reset_token`, `password_reset_token_created_at` and
  `google_2fa_recovery_codes`, and gains `email_confirmed_at` — whether an address was confirmed is durable
  state, where the token that proved it now expires and is collected. See UPGRADE.md
- The confirmation and reset URLs no longer carry the address: the token finds its own user, so a link in a
  mailbox, an archive or a referrer stops naming who it belongs to. `Models\User::getEmailConfirmationUrl()` and
  `getPasswordResetUrl()` are `createEmailConfirmationUrl()` and `createPasswordResetUrl()`, which issue the
  token they link to, and `generateVerificationToken()` / `generatePasswordResetToken()` are
  `createVerificationToken()` / `createPasswordResetToken()`, returning the token in the clear for the one moment
  it exists
- `Helpers\SecretKey` is the one place the `secretKey` param — falling back to `cookieValidationKey` — is read,
  for the token HMAC and the encrypted two-factor secret alike
- `Console\Controllers\UserTokenController` adds `user-token/clear`, which deletes the tokens that have expired
  and leaves the recovery codes, which are spent rather than aged out
- `params/index` generates a `passwordPepper` when there is none, the way it already generated the
  `cookieValidationKey`, and `params/pepper` does it on demand. `user.password_salt` records which scheme a hash
  was written under — `Models\User::PASSWORD_PEPPER`, `null`, or a legacy per-user salt — so a pepper can be
  added to or removed from a running installation without locking anyone out: the hash is reported outdated by
  `isPasswordHashOutdated()` and rewritten on that user's next successful login. Replacing an existing pepper
  still invalidates every hash written with it, so `params/pepper` never does that unattended and never defaults
  to yes
- The second factor is encrypted and has a recovery path. `user.google_2fa_secret` held the secret in the clear
  in a 16-character column and there were no backup codes, so a lost device meant an administrator.
  `Migrations\M260913160000TwoFactorAuthentication` widens the column, encrypts what is in it and adds
  `google_2fa_recovery_codes`. `Models\User` reaches the secret through `getTwoFactorAuthenticationSecret()` /
  `setTwoFactorAuthenticationSecret()` and answers `hasTwoFactorAuthentication()`; enabling 2FA issues
  `User::RECOVERY_CODE_COUNT` single-use codes, shown once on the security page and kept only as HMACs. A
  recovery code stands in for a TOTP code at the login **and** on the form that turns 2FA off, so a user who
  lost the device gets themselves out. The encryption key is the `secretKey` param, falling back to
  `cookieValidationKey`
- `user_login` has a retention. It keeps an IP address and a user agent for every login and nothing ever removed
  one, so an installation held them forever. `Modules\Admin\Module::$userLoginLifetime` sets how long they are
  kept and the new `user-login/clear` command (`Console\Controllers\UserLoginController`) deletes the rest,
  matching `trailLifetime` and `trail/clear`. The batched delete both use moved into
  `Console\Controllers\Traits\GarbageCollectionTrait`, which `TrailController` reads `$sleep` from now
- `userUpdate` is no longer a takeover of every account it reaches. It can set a password, generate a reset token
  and clear a second factor, so its holder could log in as anyone who was not the owner — including a user holding
  `authUpdate` or `admin`. `Rbac\Rules\OwnerRule` now also refuses a target whose permissions the acting user
  does not already hold, and `Migrations\M260913150000UserDeleteRule` puts the same rule on `userDelete`. An
  actor who holds every permission is answered without a lookup, so the ordinary administrator costs nothing
- The guest-facing forms no longer say which email addresses have an account. `IDENTITY_YOUR_EMAIL_WAS_NOT_FOUND`,
  the wrong-password message and `COMMON_ACCOUNT_CURRENTLY_DISABLED` were three different answers, the password
  check was skipped entirely when nothing matched — so a missing address was measurably faster to reject — and
  the recovery and resend forms confirmed an address by failing on it. `Models\Traits\IdentityTrait` reports one
  message through the new `addIdentityError()`, `Models\Forms\LoginForm` hashes against a dummy hash when no
  account matched, and `PasswordRecoverForm` / `AccountResendConfirmForm` report their ordinary success for an
  address with no account, a disabled one, or one inside the spam-protection window.
  `Web\User::$enableUserEnumerationProtection` turns all of it off for an application that would rather keep the
  messages that name the reason
- Password policy and hashing. `Models\User::$passwordMinLength` is 8 rather than 5, the new
  `$passwordMaxLength` caps a password at bcrypt's 72 bytes — past which it is silently truncated — and every
  form that takes one enforces both. `generatePasswordHash()` no longer writes a `password_salt`: bcrypt carries
  its own, and the optional `passwordPepper` param takes its place as a secret the database does not hold.
  `Models\Forms\LoginForm` rehashed and re-salted on every single login and now calls the new
  `isPasswordHashOutdated()` first, which migrates a legacy salted hash or one below the security component's
  current cost exactly once. A hash written with a `password_salt` keeps validating against it
- The login is rate limited. Nothing counted a failed attempt before, so a password or a six-digit TOTP code —
  of which three are valid in every period at the default discrepancy — could be guessed at request speed.
  `Web\User::$loginAttemptLimit` (10) and `$loginAttemptDuration` (900 seconds) drive a per-email **and**
  per-IP counter in the cache; `Models\Forms\LoginForm` refuses a login while either is over the limit, counts
  every submission that carried a credential — the code step included — and clears both on success. Set the
  limit to `0` to disable it
- `Test\TestCase` gives the test application an `ArrayCache`, so nothing a test writes to the cache reaches the
  next one
- Verification and password reset tokens expire. `user` carries a `verification_token_created_at` and a
  `password_reset_token_created_at` (`Migrations\M260913140000TokenExpiry`), `Models\User::$tokenLifetime`
  is how long one stays usable (24 hours), and `isVerificationTokenValid()` / `isPasswordResetTokenValid()`
  compare through `Security::compareString()` where the forms compared with `!==`. A successful login clears
  whatever reset token is out there, and `Models\Forms\PasswordRecoverForm::isAlreadySent()` reads the token's
  own timestamp instead of the record's `updated_at`
- A password change ends every other session. Rotating the auth key only invalidated the auto login cookies, while
  the `session` rows of the same user kept working — so a stolen session survived the password change meant to
  close it. `Web\User::destroyOtherSessions()` deletes them through the new
  `Web\DbSession::destroyUserSessions()`, and the account credentials form, the password reset, the admin user
  form and the admin's 2FA removal all call it. A user can also end them by hand through the new
  `account/logout-other-sessions` action and the `Modules\Admin\Widgets\Navs\AccountLogoutOtherSessionsButton`
  in the account dropdown
- `Modules\Admin\Controllers\UserController::updateUserAttributes()` is gone with its only caller
- The credentials email never carries a password. `Modules\Admin\Models\Forms\UserForm` generates a password
  reset token whenever `sendEmail` is set — not only for a user created without a password — and the mail links
  there, so nothing readable from a mailbox or a mail archive is a working credential.
  `UserForm::getPasswordResetUrl()` no longer returns `null` when a password was set
- A password reset, an email confirmation and a signup no longer log a user in when they owe a second factor.
  Only `Models\Forms\LoginForm` ever checked the TOTP code, so anyone who reached the mailbox — or an admin who
  generated a reset token — got a session on a 2FA account without one. `Web\User::isTwoFactorAuthenticationRequired()`
  is the one place that answers it, and the three flows refuse the automatic login instead;
  `Modules\Admin\Controllers\AccountController::actionReset()` sends a guest to the login form afterwards
- `Modules\Admin\Controllers\UserController::actionDisableGoogleAuthenticator()` is
  `actionDisableAuthenticator()`. Its route was `disable-google-authenticator` while the access rule named
  `disable-authenticator`, so the deny-by-default filter answered 403 for every request to it. It is `POST`-only
  now and reachable from the new `Modules\Admin\Widgets\Navs\UserDisableAuthenticatorButton` in the user
  action dropdown, which is shown only for a user who actually has a secret
- Cookies carry the `secure` flag over a secure connection. `Web\SessionTrait::getCookieParams()` set only
  `sameSite`, and neither the identity cookie nor the container's `Cookie` definition set it at all, so the session
  id and the auto-login key were sent over plain HTTP whenever anything reached the site that way
- `Web\Controller::$strictTransportSecurity` sends a `Strict-Transport-Security` header next to the CSP one,
  defaulting to `max-age=31536000` and only over a secure connection. Set it to `false` to opt out
- `user.login_count` is an unsigned `int`. It was a signed `SMALLINT` that every login — a cookie login
  included — increments, so an account that reached 32,767 could not log in again
  (`Migrations\M260913130000LoginCount`)
- The temporary `.cnf` `Db\Mysql\Schema` writes the database password to for `mysqldump` is created `0600`
  instead of with the process umask
- The error log no longer carries credentials. Its `maskVars` still named `User.newPassword` and friends after the
  account forms were renamed, so `AccountCredentialsForm`, `UserForm`, the two `code` fields and the account
  delete's top-level `value` — which is the password — went to disk in the clear. `logVars` dropped `_COOKIE` and
  `_SESSION`, and `_SERVER.HTTP_COOKIE` and `_GET.code` are masked, so neither the session id nor a reset token
  reaches the log
- `Web\Request::getRemoteIP()` no longer reads `X-Forwarded-For` or `Client-IP`. The headers are set by the
  client, so every request could name its own IP — which fed the signup rate limit, the `user_login` and
  `session` audit columns and any `ips` rule of an access filter. Yii resolves them behind
  `Request::$trustedHosts`, which a deployment behind a proxy has to configure. See UPGRADE.md
- `Models\Interfaces\AdminModelInterface` replaces `AdminRouteInterface` and holds everything the admin needs to
  present a model: `getAdminRoute()`, `getAdminName()`, `getAdminType()` and `getAdminIcon()`.
  `Models\Traits\AdminModelTrait` implements all but the route, which stays with the model. `TrailModelInterface`
  and `SearchableInterface` extend it, so `getTrailModelName()`, `getTrailModelType()` and
  `getTrailModelAdminRoute()` are gone — every call site reads the admin methods instead, and the trail model that
  has no page of its own declares `getAdminRoute(): false` rather than being recognised by `instanceof`.
  `Models\Traits\SearchableTrait` lost `getSearchIcon()` and `getSearchBadge()` with them: the result's icon and
  badge are the model's admin icon and type. Two behaviour changes fall out of it — a model with a `name` is named
  by it in the trail, where several ignored theirs, and `getAdminType()` is a non-empty `string` where
  `getTrailModelType()` was `?string`, so a model that declares none reports its short class name. See UPGRADE.md
- `Models\Redirect::getDisplayName()` is `getAdminName()`, and `getAdminType()` is the new `REDIRECT_REDIRECT` noun
- The index carries the tokens InnoDB throws away. Its parser splits `f2a@domain.com` into `f2a`, `domain` and
  `com`, then indexes neither its stopwords nor anything shorter than `innodb_ft_min_token_size`, so `com`, `IT`
  and `.de` were never in the index and nothing could ask for them. `Search\SearchText::getIndexTokens()` now
  appends a `__`-prefixed copy of every such token to the indexed content, and `toBooleanQuery()` asks for one
  as `+(__com* com*)` — the copy finds `domain.com`, the plain prefix still finds `commerce`. Two-character
  searches work for the first time. A stopword is required on its own and optional beside another token, so
  `the Bergfirma` finds the record without `the` and `.com` still finds the addresses. `tokenize()` no longer
  drops anything. **Run `search/rebuild`**: an index written before this finds none of it
- A searchable name is any readable property, read through the magic getter: a column, a translated or custom
  attribute, or a plain getter. `Models\Traits\SearchableTrait::getSearchAttributeValue()` no longer goes
  through `getAttribute()`, which returned `null` for everything else
- `Models\Redirect` is no longer searchable: one row per redirect drowned the results without helping anyone.
  A project that wants them back adds `Models\Interfaces\SearchableInterface` and the trait itself
- The navbar search closes on Escape and on a click outside it, wherever the focus is — a `popover="manual"`
  gets neither light dismiss nor the Escape key of its own — and it no longer animates on the way closed
- The fade that hides the overflowing breadcrumbs moved from `.breadcrumbs-list::after` to `.navbar::before`, so
  it follows the navbar instead of sitting at a fixed offset: the search input changes the navbar's width
- Added a fulltext search. A model opts in with `Models\Interfaces\SearchableInterface` +
  `Models\Traits\SearchableTrait` and a `getSearchAttributes()` of its own; `Db\ActiveRecord::behaviors()`
  attaches `Behaviors\SearchBehavior` to every such model, which writes one `search` row per record and
  configured language on insert, update and delete. The new `search` application component
  (`Search\Search`) carries the registered classes — each bundle adds its own from its `Bootstrap` through
  `extendComponent('search', ['models' => [...]])` — and the driver behind
  `Search\SearchDriverInterface`, of which `Search\MysqlDriver` is the only implementation.
  `Search\SearchQuery` builds the boolean-mode `MATCH … AGAINST` with a title boost and the per-model weight,
  falling back to a `title LIKE` when no token survives the sanitizer (`Search\SearchText`), and collapses the
  per-language rows of a record into one hit. `Console\Controllers\SearchController` adds `search/rebuild` and
  `search/clear`. `M260913120000Search` creates the `search` table, utf8mb4 and with its two fulltext indexes.
  `Models\User` and `Models\Redirect` are opted in. See UPGRADE.md
- Added the admin search: `Modules\Admin\Controllers\SearchController` with `suggest` and `index`, the
  `Widgets\Search\SearchResultList` both render, the `Search\SearchResult` DTO a model returns from
  `getSearchResult()` — `null` hides the hit from the current user — and `Search\SearchResultBuilder`, which
  loads the hits one query per class and over-fetches because that check drops them after the fact.
  `Modules\Admin\Widgets\Navs\NavBar` renders a search button that morphs into the autocomplete input
  (`includes/search.ts`). The admin search is never scoped to a tenant: `search.tenant_id` and `search.status`
  exist for the frontend presets on `SearchQuery`
- `Modules\Admin\Module::$enableSearch` turns the whole feature off in one place: the navbar button, both admin
  actions (404), the behavior's writes and the console commands
- `Widgets\Traits\VisibilityTrait` understands the role markers of `yii\filters\AccessRule`: `ROLE_ANY` (`*`, which
  it already had) and the new `ROLE_AUTHENTICATED` (`@`), matched before a permission lookup.
  `Modules\Admin\Widgets\Navs\DashboardNavItem` and `SystemNavItem` declare `roles` instead of computing
  `visible`, so a project can widen them the same way as every other nav item
- `Widgets\Buttons\FileUploadButton::selectOob()` and `Html\Custom\FileUpload::selectOob()` pass an
  `hx-select-oob` list to the upload, which `components/FileUpload.ts` hands to `htmx.swap()`, so a counter that
  sits outside the swap target is refreshed along with it
- A file upload closes the dropdown it was started from: the swap replaces the target, not the popover the button
  sits in, which stayed open behind it
- An htmx request whose session is gone is answered with `HX-Refresh` instead of a fragment: `Web\User::afterLogout()`
  and `loginRequired()` mark the response through the new `Web\Response::setHtmxRefresh()`, which drops the
  `HX-Location` htmx would otherwise process first. The page that made the request still carries the identity and the
  CSRF token it was rendered with, so it has to be loaded again rather than patched
- A user created in the admin no longer needs a password: `Modules\Admin\Models\Forms\UserForm` dropped the
  `required` rule of its insert scenario and generates a password reset token instead, which the credentials
  email offers as a reset link in place of the password
- `Models\Trail::TYPE_DEFAULT` is `13`, a type of its own for a plain message, and no longer the `TYPE_CREATE`
  the interface constant resolved to. `beforeValidate()` assigns it, and `M260913110000TrailType` moves the
  column default with it, so a trail that names no type is a message instead of a bogus create
- Added `Html\Img::fetchPriority()`
- Private properties dropped their `_` prefix, so a cache now carries the name of the magic property it backs.
  In a class that uses one of the affected traits this shadows the getter: `$this->ancestors`, `$this->children`
  and `$this->descendants` in a model using `Models\Traits\MaterializedTreeTrait` or `NestedTreeTrait`, and
  `$this->customAttributes` in one using `Models\Traits\CustomAttributesTrait`, read the unpopulated cache
  instead of calling `getAncestors()`, `getChildren()`, `getDescendants()` or `getCustomAttributes()`. Call the
  getter. Access from outside the class is unchanged
- `Base\Traits\ModelTrait::getTraitNames()`, `getTraitRules()` and `getTraitAttributeLabels()` are removed. They
  discovered `get<Trait>Rules()` / `get<Trait>AttributeLabels()` methods by reflection and naming convention — the
  same magic `Widgets\Attributes\Configure` was removed for. A class that uses such a trait now spreads its
  methods in its own `rules()` and `attributeLabels()`
- `Models\Traits\IconFilenameAttributeTrait` is removed, together with the `ICON_FILENAME_ATTRIBUTE_ICON`
  message. `Models\CustomAttributes\IconCustomAttribute` replaces it and needs no column, no rules and no label
  of its own; `Helpers\IconHelper::getIconFilenames()` stays and is what it reads. See `UPGRADE.md`
- `Web\Controller` triggers the new `Controller::EVENT_CONFIGURE` from `init()`, so a bundle `Bootstrap` can
  configure any web controller from the outside — the counterpart to `Widgets\Widget::EVENT_CONFIGURE`. It cannot be
  an `EVENT_BEFORE_ACTION` handler: `Component::trigger()` attaches the behaviors before it calls a handler, and a
  class-level handler runs after the instance-level ones an `ActionFilter` registers, so an `AccessControl` has both
  been built and run by then. `Modules\Admin\Controllers\DashboardController::addRoles()` listened for
  `EVENT_BEFORE_ACTION` and therefore never widened the dashboard's access rule — every role added by
  `yii2-cms`, `yii2-cms-shopify`, `yii2-config`, `yii2-location`, `yii2-media` and `yii2-tenant` was ignored and
  only a user with `userCreate` or `authUpdate` could open the dashboard
- `Widgets\Widget` triggers `Widget::EVENT_CONFIGURE` from `configure()`, between the widget's own defaults and its
  `prepare()` closures, so `Event::on(SystemNavItem::class, Widget::EVENT_CONFIGURE, ...)` adds to any widget from
  anywhere — a bundle `Bootstrap` needs no admin submodule for it. The trigger walks the class hierarchy, so a
  listener on a parent widget class fires for every subclass. `Widgets\Forms\ActiveForm::configure()` and
  `Widgets\Navs\Dropdown::configure()` call `parent::configure()`, which they skipped: `prepare()` closures never
  ran on a form or a dropdown
- `Widgets\Attributes\Configure` and `Widgets\Traits\ConfigureAttributesTrait` are removed. A class that used the
  attribute to run a trait hook calls that method from its own `configure()`:
  `Widgets\Grids\Toolbars\GridToolbar` calls `Widgets\Traits\StickyTrait::addStickyClass()`,
  `Cms\Modules\Admin\Widgets\Grids\Buttons\FrontendLinkButton` and
  `Cms\Modules\Admin\Widgets\Navs\FrontendLink` call `FrontendUrlTrait::configureDefaultUrl()`
- `Modules\Admin\Widgets\Navs\MainMenu` extends `Widgets\Navs\Nav` instead of wrapping one, and adds its items
  in `configure()`: the three skeleton items and then every admin submodule implementing
  `Modules\Admin\ModuleInterface`. `Modules\Admin\Module::aside()` is gone with it and the module no longer
  implements `ModuleInterface`; `ModuleInterface::aside()` stays for the submodules. A subclass overriding
  `renderContent()` or `getNav()` has nothing to override any more
- `Widgets\Navs\Traits\ItemTrait` keeps the string keys named arguments give `addItem()`, and `items()` no longer
  reindexes, so `addItem(trail: ...)` replaces a named item and the new `removeItem(string ...$names)` drops one.
  `Modules\Admin\Widgets\Navs\SystemNavItem` names its three default items `log`, `trail` and `redirect`
- `Widgets\Panels\Dashboard` adds the modules' items in `configure()` rather than in the constructor, so a
  configured `items` is no longer silently replaced by them
- The new `Console\Controllers\HelpController` replaces `yii\console\controllers\HelpController` in
  `Console\Application::coreCommands()`. Yii reflects the controller classes it discovers by scanning the
  filesystem, but adds every `controllerMap` key unchecked and lets `getCommands()` instantiate it to find out what
  it is — so a web controller mapped into a module (the host application maps `admin/dashboard` this way) was built
  under the console application, which has no `user` component. Every unknown console command therefore reported
  `Calling unknown method: Console\Application::getUser()` instead of the real error, and no command ever got a
  "Did you mean one of these?" suggestion. The override runs `controllerMap` entries through
  `validateControllerClass()` as well; a definition whose class cannot be determined without building it is left
  alone. The listed commands are unchanged
- `Models\Forms\DeleteForm::formName()` returns `''`, so `value` lives at the top level of the request and both
  widgets that post it agree. `Widgets\Buttons\DeleteButton` names its confirmation input `value` while
  `Widgets\Forms\DeleteActiveForm` named it `DeleteForm[value]`, so a `load($this->request->post())` behind a delete
  button never loaded anything and the delete silently did nothing: `UserController::actionDelete()` and
  `Tenant\Modules\Admin\Controllers\TenantController::actionDelete()` both returned 200 with the record still in
  place. `AccountController::actionDelete()` and `Media\Modules\Admin\Controllers\FolderController::actionDelete()`
  worked around it with `load($post, '')` and drop that argument again. A project that posts the prefixed name, or
  reads the `deleteform-value` input id, has to use `value`
- Account deletion moved into the new `Modules\Admin\Widgets\Navs\AccountActionDropdown`, which the new
  `Modules\Admin\Widgets\Navs\AccountHeader` carries on all three account pages, replacing the `Widgets\Navs\Header`
  they configured by hand. The delete form is gone from the settings page, and with it the alert that told the owner
  why they could not delete their account — `Modules\Admin\Widgets\Buttons\AccountDeleteButton` is simply not
  rendered for them. `AccountController::actionDelete()` reads the unprefixed `value` the button posts
  (`load($post, '')`), as `Media\Modules\Admin\Controllers\FolderController` does
- Account deletion verifies the password through `User::validatePassword()`. `Models\Forms\DeleteForm` compares the
  typed value with the model attribute it names, and `User` has no `password` attribute, so confirming the deletion
  threw `UnknownPropertyException` — on rendering the form as well as on submitting it. The new
  `Models\Forms\AccountDeleteForm` and `Modules\Admin\Widgets\Buttons\AccountDeleteButton` hold the password path.
  `Widgets\Buttons\DeleteButton` reads the same attribute to build its input's `pattern`, so the button overrides
  `getInput()` for a password field without one
- `Models\Forms\DeleteForm` gained `getExpectedValue()` and `isValidValue()`, which `validateValue()` now goes
  through. `getExpectedValue()` is what the form renders as the input's `pattern`, so a secret that is verified
  rather than compared returns `null` from it and overrides `isValidValue()` — a password must never reach the
  markup. `Widgets\Forms\DeleteActiveForm` omits the `pattern` for a `null` expected value
- New message keys `ACCOUNT_CONFIRM_DELETE` and `ACCOUNT_DELETE_TYPE_PASSWORD`, which replace the confirmation and
  the delete message the account view passed inline
- The admin account page was split into three: `AccountController::actionUpdate()` keeps the username, language,
  timezone and custom attribute fields, the new `actionCredentials()` holds the email and password fields and the
  new `actionSecurity()` the two-factor authenticator form, which `actionEnableAuthenticator()` and
  `actionDisableAuthenticator()` now redirect back to. The new
  `Modules\Admin\Widgets\Navs\AccountSubmenu` links the three, and
  `Modules\Admin\Widgets\Forms\AccountActiveForm` lost the email, password and current password fields to the new
  `AccountCredentialsActiveForm`
- `Models\Forms\AccountUpdateForm` carries the settings fields only; the email and password fields moved to the new
  `Models\Forms\AccountCredentialsForm`, which requires `oldPassword` for every save as long as the user has a
  password, instead of only when the email or the password changed. The blank field now reports
  `ACCOUNT_UPDATE_CURRENT_PASSWORD` rather than "is invalid"
- `Models\Forms\Traits\UserFormTrait` no longer declares `$repeatPassword` — a form that has the field declares it
  itself — and gained `getUserAttributeNames()`, the allowlist of user attributes `load()` accepts. A form that
  renders only some of the user's fields must return them, or the others can still be set through a crafted request:
  without it the split account pages would have let `/admin/account/update` change the email with no password check.
  `null`, the default, keeps loading every safe attribute
- Removed `Models\User::$picture` and everything around it: `Models\Forms\UserPictureForm`,
  `User::deletePicture()`, `getPictureUrl()`, `getUploadPath()` / `setUploadPath()`, the
  `account/picture` and `user/delete-picture` actions and `Models\Forms\Traits\UserFormTrait::$upload` /
  `uploadUserPicture()`. Profile pictures had no upload UI left in v3
- Removed the `first_name`, `last_name`, `birthdate`, `city` and `country` columns from `user`, together with
  `User::getFullName()`, `User::getCountries()` and the `getFirstNameField()`, `getLastNameField()`,
  `getCityField()` and `getCountryField()` methods of `Modules\Admin\Widgets\Forms\Traits\UserActiveFormTrait`.
  `User::getInitials()` falls back to the username. `M260913100000UserAttributes` copies the five columns into
  `custom_attributes` before dropping them; a project that still needs them declares them as custom attributes.
  See UPGRADE.md
- `Models\User` is a `CustomAttributeInterface`, and `user` has a `custom_attributes` column. The account and user
  forms render one field per declared definition
- `Widgets\Forms\Traits\CustomAttributeFieldsTrait::getCustomAttributeFields()` takes an optional model, for a form
  whose own model wraps the record — as `UserForm` and `AccountUpdateForm` wrap `User`
- `Models\Queries\UserQuery::matching()` searches `name` and `email` only, and `nameAttributesOnly()` /
  `selectListAttributes()` no longer select the dropped columns
- The message keys `USER_FIRST_NAME_LABEL`, `USER_LAST_NAME_LABEL`, `USER_BIRTHDATE_LABEL`, `USER_CITY_LABEL`,
  `USER_COUNTRY_LABEL`, `USER_PICTURE_LABEL` and `USER_UPLOAD_LABEL` were dropped from every language file
- `esbuild.config.js` exports the shared `buildScripts()` / `buildStyles()` builders every bundle's `esbuild.js`
  now calls, so a bundle only declares its entry points. Their sass load paths include the skeleton's
  `resources/assets/src/css` and `node_modules`, which replaces the `../../../../../yii2-skeleton/…` import chains
  with `@use "shared/breakpoints" as *` and removes the need for a bundle to install esbuild itself
- A dropdown tears its open state down when the popover is removed from the DOM. A partial htmx swap over an open
  dropdown — every grid filter is one — hides the popover without firing `toggle`, so `<html>` stayed at
  `overflow: hidden` with the page unscrollable, and floating-ui's `autoUpdate` kept its window listeners and went
  on repositioning a detached element. The new `includes/teardown.ts` runs the teardown on disconnect as well as on
  close, and the scroll lock moved out of `includes/dropdown.ts` into `includes/scrollLock.ts`, where it counts its
  owners instead of writing the `<html>` styles per dropdown
- A success flash message restarts its five-second remove timer on mouse-out; hovering it cleared the timer for
  good, so the message stayed until it was closed by hand
- Removed `yiisoft/yii2-authclient` and everything built on it: `Auth\Clients\ClientInterface`,
  `Auth\Clients\Facebook`, `Models\AuthClient`, `Models\Forms\AuthClientSignupForm`,
  `Modules\Admin\Widgets\Grids\AuthClientGridView`, `Modules\Admin\Widgets\Panels\AuthClientListGroup`,
  `Web\Application::getAuthClientCollection()`, the `authClientCollection` component,
  `Base\Traits\ApplicationTrait::setFacebookClientComponent()`, `Models\User::getAuthClients()`,
  `Models\Forms\LoginForm::$enableFacebookLogin` / `isFacebookLoginEnabled()`,
  `Models\Forms\SignupForm::$enableFacebookSignup` / `isFacebookSignupEnabled()`, and the `account/auth`,
  `account/deauthorize` and `user/deauthorize` actions. `M260912130000AuthClient` drops the `auth_client` table
  and the trails pointing at it. See UPGRADE.md
- Removed `I18n\Lang`: `yii message` extracts `Yii::t()` call sites only, so with `removeUnused` every key reached
  through the facade was dropped from the message files on the next regeneration. Key-based translation is unchanged,
  the calls go back to `Yii::t()`
- `Modules\Admin\Controllers\UserLoginController` extends `Web\Controller` instead of `yii\web\Controller`, so it
  gets the CSP header, the flash helpers and `$webuser` like every other admin controller
- Controllers and the admin controller traits read the web user from `Web\Controller::$webuser`, widgets from
  `Widgets\Widget::$webuser`, instead of calling `Yii::$app->getUser()`. Both are assigned in the constructor, so a
  *subclass* constructor still has to call the component itself — it runs first
- `Widgets\Grids\Columns\Column` gained `$webuser`, so `Widgets\Traits\VisibilityTrait::isVisible()` reads the
  property for both of its hosts
- Controllers read `$this->request` / `$this->response` instead of `Yii::$app->getRequest()` / `getResponse()`; both
  are resolved in `yii\base\Controller::init()`, so code running before `parent::init()` still needs the component
- `Db\Traits\MigrationTrait` resolves the connection with the migration's own `$this->getDb()` instead of
  `Yii::$app->getDb()`, so `migrate --db=` is honoured; `Web\User` writes the login row through `UserLogin::getDb()`
- `Db\ActiveQuery::selectWith('user')` joins a hasOne relation and populates it from the same row, where Yii's
  `joinWith()` joins and then runs a second query for the eager load. The joined table keeps its table name as alias
  as with `joinWith()` (`alias()` it in the callback to join a table twice), the relation's own `where` moves into the
  ON clause (qualify its columns), a LEFT JOIN without a match populates `null`, and a hasMany or `via` relation is
  refused — a filtered or limited join would hand out a partial set. The related records are populated by the relation's own query, so a nested `with()` and the translations of a
  `TranslationInterface` model apply as they would after `with()`. `selectJoinedRecord()` is the protected building
  block for a join the query builds itself
- `Modules\Admin\Controllers\UserController::actionDeauthorize()` reads the `identity` relation it uses; it joined a
  `user` relation `AuthClient` does not have, so the action threw
- Private and protected properties lose their `_` prefix (`ActiveQuery::$status`, the I18n query's translation
  state, the permalink and tenant collection state); a subclass reading `self::$_status` reads `self::$status`
- A `Redirect` may name a host: `request_uri` is `www.example.com/old` or `old`, and `Web\ErrorHandler` matches a
  404 against both forms with the host of the URL manager's `hostInfo`, the host-qualified record first. It sends
  the redirect response itself instead of calling `Application::end()`, so it works in the functional test browser
  too. `ErrorHandler::checkRedirectRequestUri()` is `redirectRequestUri(): bool` and
  `findRedirectByRequestUri()` takes the list of candidates
- `Test\TestCase::tearDownApplication()` replaces `Yii::$container`: a request writes to it (the cookie domain,
  for one) and the next test must not inherit that. `config/test.php` sets `params['tenantUrl']`, which the cms
  tenant seed migration needs to migrate a test database

- `Test\TestCase` loads the fixtures inside the test transaction and no longer unloads them, and the fixtures extend
  the new `Test\Fixtures\ActiveFixture`, which never resets the auto-increment counter: a record a test creates has
  no predictable id. DDL a test needs goes into the new `setUpSchema()` / `tearDownSchema()` hooks, which run outside
  the transaction; a test that runs DDL mid-test gets its fixtures unloaded by `tearDown()` instead. The table schema
  is cached across the tests of a process. `config/test.php` and `Test\TestCase` honour paratest's `TEST_TOKEN`:
  worker N uses the database `<dbname>_N` and the runtime directory `runtime/paratest/N`. `setUp()` also clears
  `$_GET`, `$_POST`, `$_COOKIE`, `$_REQUEST` and `$_FILES`, which a functional test's request used to leave behind
- Added `Models\Interfaces\AdminRouteInterface`, the contract behind `getAdminRoute()`. `Models\User` and
  `Models\Redirect` implement it, and `Models\Traits\TrailModelTrait::getTrailModelAdminRoute()` asks for the interface
  instead of sniffing the method with `method_exists()`, so a model that implements it no longer needs to bridge the
  two itself
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