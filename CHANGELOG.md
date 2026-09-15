## 3.0.0 (in development)

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