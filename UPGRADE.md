# Upgrade Guide

## 3.0.0 — Types and statuses are objects

`getTypes()` and `getStatuses()` no longer return arrays. They return a list of definition objects, and the
platform refuses anything else: the first read throws `InvalidConfigException` naming the model.

```php
// before
public static function getTypes(): array
{
    return [
        self::TYPE_DEFAULT => [
            'name' => Yii::t('app', 'Page'),
            'hiddenFields' => ['content', '#assets'],
            'class' => Page::class,
        ],
    ];
}

// after
public static function getTypes(): array
{
    return [
        EntryType::make(self::TYPE_DEFAULT)
            ->name(Yii::t('app', 'Page'))
            ->hiddenFields('content', AssetModelInterface::FIELD_ASSETS)
            ->modelClass(Page::class),
    ];
}
```

The value moved from the array key to the constructor argument, every key is a setter of the same name, and
`class` is `modelClass()`. A misspelled key was silent; a misspelled setter is an `Error`.

Reads change with it. `getTypeOptions()` is gone:

```php
// before
$viewFile = $this->getTypeOptions()['viewFile'] ?? null;
$name = static::getStatuses()[$this->status]['name'] ?? '';

// after
$viewFile = $this->getType()?->getViewFile();
$name = static::findStatus($this->status)?->getName() ?? '';
```

`getTypes()` is the declaration and is read by nothing but the registry. Everything else reads
`getTypeDefinitions()` (indexed by value, validated, cached), `findType()` or `$model->getType()`, which is
`null` for a row whose type the code no longer declares. `getStatuses()` mirrors it with
`getStatusDefinitions()`, `findStatus()` and `getStatus()`.

**Never mutate a definition you were handed.** `DefinitionRegistry` caches it and every record of that value
shares the instance; a configuration to be reused across several types is a named constructor on your own type
subclass, which makes a fresh object per call. The cache is keyed by the application language as well, since a
`name` is a `Yii::t()` result, and is reset with the application.

A project option that used to live in the array — `entriesPerPage`, `headlineTag` — has no home in a bag any
more; there is deliberately no `option()` escape hatch. Extend the bundle's type class instead:

```php
final class SectionType extends \Hirtz\Cms\Models\Types\SectionType
{
    protected ?int $entriesPerPage = null;

    public function entriesPerPage(?int $entriesPerPage): static
    {
        $this->entriesPerPage = $entriesPerPage;
        return $this;
    }

    public function getEntriesPerPage(): ?int
    {
        return $this->entriesPerPage;
    }
}
```

and point the model at it:

```php
public static function getTypeClass(): string
{
    return SectionType::class;
}

public function getType(): ?SectionType
{
    return static::findType($this->type);
}
```

New on the base class: `available(Closure|bool)`, whether the type is offered for a record in the admin. That
is what the asset types' `visible` key meant; a cms section type's `visible()` keeps its own meaning, the
frontend render filter.


## 3.0.0 — The session and auto login cookies are renamed

Everyone is logged out once when this deploys. The auto login cookie is `_auth` (was Yii's `_identity`) and the
session cookie is `_session` (was PHP's `PHPSESSID`).

The rename is what disposes of the cookies the upgrade already invalidated — `M260913180000PasswordScheme`
rotates `auth_key` for every account whose v2 hash it drops — and a migration could not, since it cannot reach a
browser. It also escapes a trap that made those stale cookies unclearable: `secure` is derived from the request,
so a host answering on both http and https writes a `Secure` copy beside the plain one, and a browser then
refuses every plain HTTP response the right to overwrite *or* delete that name (RFC 6265bis §5.4, "leave secure
cookies alone"). The stale cookie was frozen in the browser and the user logged out on every session lapse,
while the server sent the correct headers throughout.

A fresh name has no `Secure` twin, but the trap re-arms on it as soon as the site is used over both schemes
again. **Pin the flag on any host that answers on both** — a local `*.localhost` served by Herd or Valet, or a
staging box without a redirect:

```php
'components' => [
    'session' => ['cookieSecure' => false],
    'user' => ['cookieSecure' => false],
],
```

Both default to `null`, which keeps the previous per-request behaviour, and both accept `true` to pin a cookie
as `Secure` on an HTTPS-only deployment. A project that named either cookie itself keeps its own name:
`components.user.identityCookie` and `components.session.name` are unchanged as configuration.

To diagnose a browser already stuck in this state, set two cookies in one response — one under the name in
question and one under any other name. If only the other one lands, a `Secure` twin exists and nothing served
over http can remove it; the cookie has to be cleared in the browser, or the name retired.

## 3.0.0 — The admin path is a param

`Modules\Admin\Module::$alias` is gone. The path the admin is reached under is `params['adminAlias']`, read by
`Base\Traits\ApplicationTrait::getAdminAlias()` and defaulting to `admin`:

```php
// config/params.php
return [
    'adminAlias' => 'backend',
];
```

The property was never read — the URL rules took the value out of the raw `modules.admin.alias` config array, so
a project that set it on a `Module` subclass was silently ignored, and reading it off the module instance would
have meant building the module on every request. A project that configured `modules.admin.alias` moves that
value to the param.

Moving the admin now also closes the default path: a route no URL rule matched falls back to the request path,
which kept `admin/…` serving beside the new prefix. `Module::beforeAction()` refuses that one fallback, and only
when the alias differs — a rule of the project's own that routes into the module parses as before.

## 3.0.0 — The admin has its own languages

`Modules\Admin\Module::$languages` is the list the admin interface is offered in, and it is no longer the
application's content languages. The two were the same list only because the admin used to follow whatever
language the URL manager resolved; it now overrides that unconditionally, so a project can edit content in
languages the admin has no translation for.

```php
'modules' => [
    'admin' => [
        'languages' => ['de', 'en-US'],
    ],
],
```

It defaults to `I18N::getLanguages()`, so a project that says nothing keeps what it had. Three consequences:

- **A single language is pinned.** The admin runs in it, `Widgets\Buttons\LanguageDropdownButton` renders
  nothing and the account's language field is hidden — there is nothing to pick. A single-language project
  therefore loses the language column it never used.
- **The account language is validated against the admin list**, by `Models\User::getLanguages()` and through it
  the `DynamicRangeValidator` on the attribute. An account whose stored `language` is no longer offered falls
  back instead of switching the admin into a language it has no messages for — which is what happened before.
- **The session override moved to the module.** `I18N::$sessionKey`, `I18N::getSessionLanguage()` and
  `I18N::setSessionLanguage()` are `Module::$languageSessionKey`, `Module::getSessionLanguage()` and
  `Module::setSessionLanguage()`; `Modules\ModuleTrait` is the skeleton's accessor for the module, matching the
  other bundles. `I18N` keeps `getLanguages()`, `setLanguages()` and `hasLanguage()` for the content languages.

**Russian and both Chinese translations are gone**, along with their flags: `messages/ru`, `messages/zh-CN` and
`messages/zh-TW` in every bundle, their entries in `I18N::$languageLabels` and `messages/config.php`, and the
`ru`/`zh-CN`/`zh-TW` flag images. The shipped set is `de`, `en-US`, `fr` and `pt`. A project that needs one of
them back adds the language to `$languageLabels`, a flag rule to its own CSS and its own `messages/<lang>`
directory — the message source reads the bundle's path, so the file has to live there or be pointed at through
`I18N::$translations`. The `country` category went with them, so a frontend rendering country names in Russian
or Chinese needs its own source too.

## 3.0.0 — One permission per model

The 46 verb permissions (`entryCreate`, `entryUpdate`, `entryDelete`, `entryOrder`, …) are 14 nouns, one per
model the admin manages. `create`, `delete` and `order` were already parents of their `update`, so the only
states the split could actually express were ones no project used; every project assigns roles.

| Bundle   | New permission                  | Replaces                                                                               |
|----------|---------------------------------|----------------------------------------------------------------------------------------|
| skeleton | `user`                          | `userCreate` `userUpdate` `userDelete`                                                 |
| skeleton | `authUpdate` (kept)             | —                                                                                       |
| skeleton | `trailIndex` (kept)             | —                                                                                       |
| skeleton | `redirect`                      | `redirectCreate`                                                                        |
| cms      | `entry`                         | `entry*` `entryAsset*` `entryCategoryUpdate` `section*` `sectionAsset*`                 |
| cms      | `category`                      | `category*`                                                                             |
| media    | `file`                          | `file*`                                                                                 |
| media    | `folder`                        | `folder*`                                                                               |
| location | `location`                      | `location*`                                                                             |
| location | `tag`                           | `tag*`                                                                                  |
| tenant   | `tenant`                        | `tenant*`                                                                               |
| config   | `config`                        | `configUpdate`                                                                          |
| shopify  | `shopifyProduct`                | `shopifyProductUpdate`                                                                  |
| shopify  | `shopifyWebhook`                | `shopifyWebhookUpdate`                                                                  |

`authUpdate` keeps its verb and its value: it is the one that can grant permissions, and it must stay separate
from `user`. `trailIndex` keeps its, because it is read-only.

**The migration widens.** `M260914100000AuthItems` and its six siblings grant the new item to every parent and
every assignee of *any* of the old ones, then delete those. An account or a project role that held `entryUpdate`
alone now holds `entry` — which includes creating, deleting and reordering entries, their sections and their
assets. Check your assignments before you run it if that matters.

The three roles are unchanged: `admin` holds everything, `author` holds `entry` and `category`, `media` holds
`file` and `folder`. An editor who also uploads still needs `media` beside `author`.

### What to rename

- Every `Model::AUTH_*_CREATE` / `_UPDATE` / `_DELETE` / `_ORDER` constant is one `Model::AUTH_<MODEL>`:
  `User::AUTH_USER`, `Redirect::AUTH_REDIRECT`, `Entry::AUTH_ENTRY`, `Category::AUTH_CATEGORY`,
  `File::AUTH_FILE`, `Folder::AUTH_FOLDER`, `Location::AUTH_LOCATION`, `Tag::AUTH_TAG`, `Tenant::AUTH_TENANT`,
  `Config::AUTH_CONFIG`, `Product::AUTH_SHOPIFY_PRODUCT`, `Webhook::AUTH_SHOPIFY_WEBHOOK`. `Section` and the
  asset models declare none — they are edited through their entry and use `Entry::AUTH_ENTRY`.
- `Models\Asset::getPermissionName(string $action)` is `getPermissionName()`, and the `can(string $action, Asset)`
  of the media asset grids is `can(Asset)`.
- `findEntry()`, `findSection()`, `findCategory()`, `findFile()`, `findFolder()`, `findTenant()`,
  `findLocation()` and `findTag()` lost their permission argument — `AccessControl` has already answered the
  same question for the action. `findUser()` keeps it.
- Every `can()` call loses its record: `can(Entry::AUTH_ENTRY)`, not `can(..., ['entry' => $entry])`. The one
  exception is the `user` param on `user` and `authUpdate`.

### `OwnerRule` is policy in `Web\User` now

`Rbac\Rules\OwnerRule` and the `userUpdateRule` row are gone, and with them the only `yii\rbac\Rule` the
platform shipped. `Web\User::can()` reads `$params['user']` and asks `canManageUser()`, which is the rule
verbatim: the site owner and anyone holding a permission the acting user lacks stay unreachable, and an actor
holding every registered permission is still answered without a lookup. The migration clears every `rule_name`
and empties `auth_rule`; the table stays, because `yii\rbac\DbManager::loadFromCache()` reads it.

A project that wants a per-record rule of its own still has Yii's mechanism — a `rule_name` on its own
permission and `roleParams` on its `AccessRule`. The platform simply no longer ships one.

`Widgets\Traits\VisibilityTrait::$roles` is unchanged: a nav item still names permissions, `User::ROLE_ANY`
or `User::ROLE_AUTHENTICATED`, and still ORs them.

### Descriptions and trail messages are pointers, not text

`I18n\Message` holds a `category`, a `key` and optional `params`. It serializes to
`{"category":"cms","key":"AUTH_ENTRY_DESCRIPTION"}` and renders through `Yii::t()` in the *current* language,
so one row reads German to one administrator and English to the next.

- `auth_item.description` stores the JSON. The column type is unchanged, and `Message::fromJson()` returns a
  literal message for anything that is not a pointer, so a row an older version wrote still renders — as the
  English it was written in. `Models\AuthItem::getLabel()` is what the permissions page shows.
- `trail.message` stores the JSON for an order trail. `Trail::createOrderTrail()` takes a `Message`,
  `Trail::getMessage()` returns the rendered text, and the `message` of a `Trail::getTypes()` entry is a
  `Message` rather than text rendered in `sourceLanguage`.
- An assign or revoke trail no longer copies the description at all: `Rbac\DbManager::createTrail()` writes
  `data = ['name' => …, 'type' => …]`, and the grid looks the item up and renders its current label, falling
  back to the name when the item is gone and to the stored text for a row written before 3.0.
- `Widgets\Grids\Traits\MessageSourceTrait` is deleted. It tried to translate rendered English back by walking
  every registered message source, which missed for `UPPER_SNAKE_CASE` keys — that is why the German admin saw
  English descriptions.

**A `Message::make()` key lives nowhere else**, so `messages/config.php` has to list it as a translator or
`yii message` drops it:

```php
'translator' => ['Yii::t', '\\Yii::t', 'Message::make'],
```

### A project with its own permissions

One line in a migration, one message key, one name in the `AccessControl`:

```php
$this->addPermission(Invoice::AUTH_INVOICE, Message::make('app', 'AUTH_INVOICE_DESCRIPTION'), User::AUTH_ROLE_ADMIN);
```

`Db\Traits\MigrationTrait` also has `replaceAuthItems(array $old, string $new)` for collapsing a project's own
verb permissions the same way, and `restoreAuthItems()` for the way back down.

## 3.0.0 — v2 passwords are not carried over

**Every user who still has a v2 password has to set a new one.** `Migrations\M260913180000PasswordScheme` drops
those hashes, so nobody keeps a password that v2's five-character minimum let them choose, and every hash in a
v3 database is peppered.

Affected is any account whose hash carries a v2 per-user salt. Anything already written under a v3 scheme — and
any account created without a password — is left alone. Count them **before** you migrate, while the column
still has its old name:

```sql
SELECT COUNT(*) FROM user WHERE password_salt IS NOT NULL AND password_salt != 'pepper';
```

On a database coming straight from v2 that is every user who has ever set a password. **If those users are your
customers rather than a handful of administrators, this is a support event** — plan the announcement before you
run it, not after.

### The upgrade, in order

```bash
# 1. Back up. The dropped hashes cannot be recovered, and `migrate/down` does not bring them back.
./yii migrate/backup

# 2. Absolute URLs need a host in a console application, and Yii will not guess one.
#    Set components.urlManager.hostInfo (and baseUrl) for the console config first — see below.

# 3. Apply the migration. It reports how many passwords it invalidated and sends nothing.
./yii migrate

# 4. Mail the reset links, once you are ready for the inbox traffic.
./yii upgrade/passwords
```

Step 4 is deliberately not part of step 3: a migration runs in CI, on staging and on every developer's machine,
and must never mail your users. It is safe to repeat — it targets every user without a password, so a second run
only reaches those who have not set one yet. That also means it mails accounts that were created without a
password and never finished, which is usually what you want.

The console needs to know where the link points:

```php
'components' => [
    'urlManager' => [
        'hostInfo' => 'https://www.example.com',
        'baseUrl' => '',
    ],
],
```

Without it `upgrade/passwords` stops and tells you so rather than mailing a broken link. The links land on
`account/reset`, which `Web\User::$enablePasswordReset` can switch off — an installation that keeps it off has
to turn it on for the duration, or set every password with `user/password` below.

### What survives, and what does not

| | |
|---|---|
| Password | Gone. The account cannot log in until the reset link is used. |
| Remember-me cookies | Gone — `auth_key` is rotated, since those cookies were issued against the old password. |
| Open sessions | **Kept.** Anyone signed in stays signed in, including the administrator running the upgrade — which is what keeps you from locking yourself out of your own site while the emails go out. |
| Two-factor authentication | Kept, and still required. Resetting the password does not sign the user in when they owe a code; they finish at the login form. |
| Everything else | Untouched — roles, profile, trail, login history. |

If the only administrator is locked out and the mailer is not an option, set a password directly:

```bash
./yii user/password admin@example.com
```

### `password_salt` is `password_scheme`

bcrypt salts its own hashes, so the column never needed to hold one. It records which scheme a hash was written
under instead — `Models\User::PASSWORD_PEPPER` or `null` — which is what lets the `passwordPepper` param be
added, removed or rotated on a running installation: a hash whose scheme disagrees with the configured pepper is
reported by `isPasswordHashOutdated()` and rewritten on that user's next successful login. Code that read
`password_salt` reads `password_scheme`, and must not treat it as an input to the hash.

## 3.0.0 — Tokens moved to `user_token`

`user.verification_token` and `user.password_reset_token` held their tokens in the clear, so one read of the
`user` table was a password reset on every account that had one. They now live in a `user_token` table, one row
per token, stored as an HMAC of the token — a read of that table is worth nothing. The 2FA recovery codes moved
to the same table (they were already hashed, and copy across unchanged).

`Migrations\M260913170000UserToken` creates the table, copies every live token into it, adds
`user.email_confirmed_at` and drops the five columns. **Existing confirmation and reset links keep working**;
they are hashed on the way in.

### `email_confirmed_at`

`isUnconfirmed()` used to mean "has a verification token". Tokens now expire and are garbage collected, which
would silently confirm every pending account, so whether an address was confirmed is a column of its own. The
migration backfills it from `updated_at` for every user that had no verification token. A model or fixture that
set `verification_token` to mark an account unconfirmed sets `email_confirmed_at` to `null` instead, and
`Models\User::confirmEmail()` is what marks it confirmed.

### What to rename

| Removed                            | Replacement                                            |
|------------------------------------|--------------------------------------------------------|
| `generateVerificationToken()`      | `createVerificationToken(): string`                    |
| `generatePasswordResetToken()`     | `createPasswordResetToken(): string`                   |
| `clearVerificationToken()`         | `clearVerificationTokens()`                            |
| `clearPasswordResetToken()`        | `clearPasswordResetTokens()`                           |
| `getEmailConfirmationUrl()`        | `createEmailConfirmationUrl(): string`                 |
| `getPasswordResetUrl()`            | `createPasswordResetUrl(): string`                     |
| `isVerificationTokenValid($token)` | a `UserToken` lookup — see `Models\Forms\AccountConfirmForm` |
| `isPasswordResetTokenValid($token)`| a `UserToken` lookup — see `Models\Forms\PasswordResetForm`  |

The `create*` methods issue the token as a side effect and return it **in the clear, once** — only its HMAC is
stored, so a URL cannot be rebuilt from a loaded record afterwards. A form that mails one holds it the way
`Modules\Admin\Models\Forms\UserForm::getPasswordResetUrl()` and
`Models\Forms\TwoFactorAuthenticatorForm::$recoveryCodes` do, and the four account mail templates now take the
URL as a `$url` variable rather than calling the model. Both `create*` methods need a saved record, so a token
is issued after the insert, not before it.

### URLs

`/admin/account/confirm` and `/admin/account/reset` no longer take an `email` parameter — the token finds its own
user. A link that still carries one keeps working (the extra parameter is ignored), but anything that *builds*
these URLs by hand must drop it.

### Retention

`./yii user-token/clear` deletes the tokens that have expired; run it from cron beside `trail/clear` and
`user-login/clear`. Recovery codes have no expiry — they are spent, not aged out — and are never collected.

## 3.0.0 — Two-factor authentication

`user.google_2fa_secret` was a plaintext 16-character column, and there were no backup codes at all, so a lost
authenticator could only be cleared by an administrator.

`Migrations\M260913160000TwoFactorAuthentication` widens the column, encrypts every secret already in it, and
adds `google_2fa_recovery_codes`; `Migrations\M260913190000TwoFactorSecret` then renames the column to
`two_factor_secret`, since everything else around it says `TwoFactor` and nothing about TOTP is Google's. The encryption key is `Yii::$app->params['secretKey']`, falling back to
`cookieValidationKey` — **keep whichever one applies**, because losing it makes every stored secret unreadable
and every user has to set up 2FA again. Rows written before the migration (their secret is not marked `enc:`)
are still read as-is, so nothing breaks if the migration has not run yet.

**Read the secret through the model, never off the column:**

| Before                        | After                                    |
|-------------------------------|------------------------------------------|
| `$user->google_2fa_secret`    | `$user->getTwoFactorAuthenticationSecret()` |
| `if ($user->google_2fa_secret)` | `$user->hasTwoFactorAuthentication()`  |
| `$user->google_2fa_secret = $s` | `$user->setTwoFactorAuthenticationSecret($s)` |

The column behind them is `two_factor_secret`, so a query or fixture that named `google_2fa_secret` has to be
renamed — but application code should be reaching for the three methods above rather than either column name.

Enabling 2FA now issues `Models\User::RECOVERY_CODE_COUNT` single-use recovery codes. Only their HMACs are
stored, so `Models\Forms\TwoFactorAuthenticatorForm::$recoveryCodes` after a successful `save()` is the one
chance to show them — the security view renders them from a flash. A recovery code is accepted wherever a TOTP
code is: at the login, and on the form that disables the second factor. Setting a new secret clears the codes,
so a project that enables 2FA from its own code should surface the ones `save()` returns.

## 3.0.0 — `userUpdate` and `userDelete` respect the permission hierarchy

`Rbac\Rules\OwnerRule` guarded the site owner and nothing else, so anyone with `userUpdate` could set a
password — or generate a reset token, or clear the second factor — for a user holding `authUpdate` or `admin`,
and then log in as them. The rule now also refuses a target holding a permission the acting user does not,
and `Migrations\M260913150000UserDeleteRule` attaches it to `userDelete` as well (it re-saves the rule itself,
which every installation needs whatever its permissions look like).

Two things follow:

- A user with a **narrow** set of permissions who also manages users can no longer edit or delete the
  administrators. If a project relied on that — a support role that resets anyone's password, say — give that
  role the permissions of the accounts it has to reach, or grant it `admin`.
- The check compares permission sets, so it reads both users' permissions. An actor who holds every registered
  permission is answered without any lookup at all, and the results are memoised per request, so an ordinary
  administrator pays nothing and a restricted one pays one lookup per distinct user on the page.

The owner remains unreachable to everyone but themselves, unchanged.

## 3.0.0 — User enumeration

The login, password recovery and confirmation resend forms answered differently for an address that has an
account and one that does not, which is a way to enumerate the accounts of an installation. They now answer the
same: one message for every login failure, a dummy bcrypt check so a missing address costs what a wrong password
costs, and an ordinary success from the recovery and resend forms whatever the address was.

The messages that named the reason — `IDENTITY_YOUR_EMAIL_WAS_NOT_FOUND`,
`COMMON_ACCOUNT_CURRENTLY_DISABLED`, `ACCOUNT_RESEND_CONFIRM_ACCOUNT`, the two spam-protection ones — are still
there and still translated, and an application that wants them back says so:

```php
'components' => [
    'user' => [
        'enableUserEnumerationProtection' => false,
    ],
],
```

A form of your own that uses `Models\Traits\IdentityTrait` reports a failure through
`addIdentityError($specificMessage)` rather than `addError('email', …)`: it substitutes the shared message, or —
when the form overrides `canRevealIdentity()` to `false`, as the recovery and resend forms do — drops
`$this->user` so the caller reports its ordinary success. A caller of such a form must therefore tolerate a
`null` `$form->user` after a successful `validate()`, which is why
`Modules\Admin\Controllers\AccountController` names `$form->email` in its flash messages.

## 3.0.0 — Passwords

The minimum length is 8 (`Models\User::$passwordMinLength`, was 5) and the new `$passwordMaxLength` caps one at
bcrypt's 72 bytes. Existing passwords are unaffected — the rules only run when one is set — but a project that
wants the old floor back configures the model through the container.

`generatePasswordHash()` stops writing `password_salt`. bcrypt salts its own hashes, so the column bought
nothing; it stays in the schema because the hashes written with it still validate against it, and
`Models\Forms\LoginForm` migrates each one to the new form on that user's next successful login, through the
new `isPasswordHashOutdated()`. The same check replaces the unconditional rehash the login did on every request,
which was pure cost, and it also picks up a raised `Security::$passwordHashCost`.

A **pepper** takes the salt's place. `./yii params` generates one into `config/params.php` when there is none,
the way it already generated the `cookieValidationKey`, and `./yii params/pepper` does it on demand. It is
appended to every password before hashing, so a leaked `user` table cannot be attacked without a secret the
database never held.

```php
// config/params.php
'passwordPepper' => '…',
```

`password_salt` records which scheme each hash was written under — `Models\User::PASSWORD_PEPPER` for a peppered
one, `null` for none, a random string for one that predates both — so **adding a pepper to a running
installation locks nobody out**: the existing hashes still validate, `isPasswordHashOutdated()` reports them, and
each is rewritten on its owner's next successful login. Removing it again works the same way.

**Replacing** a pepper is the one destructive move: the marker says a hash was peppered, not which pepper it
used, so every hash written with the old one stops matching and those users need a password reset. `params/pepper`
therefore never replaces an existing pepper unattended, and never defaults to yes. Keep it out of version control
and back it up with the rest of your secrets.

## 3.0.0 — Login rate limiting

`Models\Forms\LoginForm` now refuses a login once an email address or an IP has accumulated
`Web\User::$loginAttemptLimit` failures within `$loginAttemptDuration` seconds, and it counts a wrong TOTP code
the same as a wrong password. The counters live in the `cache` component, so **a deployment with more than one
web node wants a shared cache** (Redis, Memcached, a database cache) rather than the default per-node
`FileCache`, or the limit is per node.

Tune or disable it on the `user` component:

```php
'components' => [
    'user' => [
        'loginAttemptLimit' => 20,
        'loginAttemptDuration' => 600,
    ],
],
```

A functional test that submits many bad passwords in one application either raises the limit or calls
`Yii::$app->getUser()->resetFailedLoginAttempts($email)` between them. `Test\TestCase` now configures an
`ArrayCache`, so the counters never outlive the test that wrote them.

## 3.0.0 — Expiring tokens

A verification or password reset token lived in the database, and in every mail archive along the way, until it
was used. Both now expire after `Models\User::$tokenLifetime` seconds — 24 hours by default — and a successful
login clears the password reset token as well.

`Migrations\M260913140000TokenExpiry` adds the two timestamp columns and backfills them from `updated_at`, so a
token that is already out there is dated by the write that created it and is usually expired at once. A model
that overrides `generateVerificationToken()` or `generatePasswordResetToken()` must set the matching
`*_created_at`, or the token it writes is never valid — call the parent, or use `clearVerificationToken()` /
`clearPasswordResetToken()` to retire one.

Compare a token through `isVerificationTokenValid()` / `isPasswordResetTokenValid()` rather than reading the
column: they check the age and compare in constant time. A longer-lived invitation link is a wider
`$tokenLifetime` on the model:

```php
'container' => [
    'definitions' => [
        \Hirtz\Skeleton\Models\User::class => [
            'tokenLifetime' => 7 * 86400,
        ],
    ],
],
```

## 3.0.0 — Secure cookies and HSTS

Session, identity and application cookies now set `secure` when the request is a secure connection, and
`Web\Controller` sends `Strict-Transport-Security: max-age=31536000` on such a request. Both read
`Request::getIsSecureConnection()`, so **a deployment behind a proxy that terminates TLS has to set
`Request::$trustedHosts`** (see the client IP section above) — without it the application sees a plain HTTP
request, and neither the flag nor the header is applied.

A year of HSTS is a commitment: once a browser has seen the header it refuses to reach the host over HTTP until
it expires, subdomains included if `includeSubDomains` is added. Shorten or disable it per controller or
application-wide:

```php
'as hsts' => [
    'class' => \yii\base\Behavior::class, // or set the property on your own base controller
],
```

```php
public string|false $strictTransportSecurity = false;
```

## 3.0.0 — Client IP behind a proxy

`Web\Request::getRemoteIP()` returned `$_SERVER['HTTP_X_FORWARDED_FOR']` or `$_SERVER['HTTP_CLIENT_IP']` for
every request, whether or not a proxy set them. Both are client-controlled, so any request could claim any IP.

The override is gone. Yii's own proxy handling takes over, and it is off until the deployment says which proxies
to trust — **an application behind a load balancer or a CDN must now configure it**, or every request reports the
proxy's address:

```php
'components' => [
    'request' => [
        'trustedHosts' => [
            '10.0.0.0/8',
        ],
    ],
],
```

`Request::$ipHeaders` defaults to `[['X-Forwarded-For', ...]]`, so naming the trusted proxies is usually all it
takes; a proxy that forwards under a different header adds it there. An application that is not behind a proxy
needs no configuration and was reading a spoofable header until now.

## 3.0.0 — Admin model interface

`Models\Interfaces\AdminRouteInterface` is gone. `Models\Interfaces\AdminModelInterface` takes its place and adds
the three things every admin surface asked a model for separately — the trail grid, the trail header, the search
result, the asset grid, a page title:

```php
public function getAdminRoute(): array|false;
public function getAdminName(): string;
public function getAdminType(): string;
public function getAdminIcon(): ?string;
```

`Models\Traits\AdminModelTrait` implements all but `getAdminRoute()`, which stays with the model — only it knows
its controller, and a silent `false` would hide every link to it:

```php
class Product extends ActiveRecord implements AdminModelInterface
{
    use AdminModelTrait;

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/shop/product/update', 'id' => $this->id] : false;
    }

    public function getAdminType(): string
    {
        return Yii::t('shop', 'COMMON_PRODUCT');
    }
}
```

`getAdminName()` reads the model's `name` — through the magic getter, so a translated or custom attribute counts —
and falls back to `COMMON_MODEL_ID` with the record's id, or to `getAdminType()` when there is no id.
`getAdminType()` is the type name of a `TypeAttributeInterface` model and its short class name otherwise, so a model
with a noun of its own overrides it. `getAdminIcon()` is the type icon, then the status icon, then `null`.

### What to rename

`TrailModelInterface` and `SearchableInterface` extend `AdminModelInterface`, so the trail methods are gone:

| Removed                       | Replacement       |
|-------------------------------|-------------------|
| `getTrailModelAdminRoute()`   | `getAdminRoute()` |
| `getTrailModelName()`         | `getAdminName()`  |
| `getTrailModelType()`         | `getAdminType()`  |

Every `TrailModelInterface` is therefore an `AdminModelInterface`: add `use AdminModelTrait;` beside
`use TrailModelTrait;`, and declare `getAdminRoute()`. A relation record with no page of its own returns `false`,
which is what the old `instanceof AdminRouteInterface` check produced for it.

`getAdminType()` returns a non-empty `string` where `getTrailModelType()` returned `?string`, so a model that
declares neither a type nor an override now shows its short class name where the trail used to show nothing. A model
that has a `name` is now named by it: `Cms\Models\Section`, `Cms\Hotspot\Models\Hotspot` and
`Media\Models\Asset` used to report `COMMON_MODEL_ID` even when they had one.

`Models\Traits\SearchableTrait::getSearchIcon()` and `getSearchBadge()` are gone with them — a hit's icon and badge
are `getAdminIcon()` and `getAdminType()`. `Media\Models\Asset::getModelName()` is gone too:
`$asset->model->getAdminName()` is the name, since `AssetModelInterface` extends `AdminModelInterface`.

## 3.0.0 — Fulltext search

`M260913120000Search` creates a `search` table: one row per searchable record, id and language, with the model's
display name in `title`, its searchable attributes in `content` and two fulltext indexes over them. The admin gets a
search button in the navbar and a results page; the frontend gets the query and the DTO, the result page is the
project's.

### Opting a model in

```php
use Hirtz\Skeleton\Models\Interfaces\SearchableInterface;
use Hirtz\Skeleton\Models\Traits\AdminModelTrait;
use Hirtz\Skeleton\Models\Traits\SearchableTrait;

class Product extends ActiveRecord implements SearchableInterface
{
    use AdminModelTrait;
    use SearchableTrait;

    public function getAdminRoute(): array|false
    {
        return $this->id ? ['/admin/shop/product/update', 'id' => $this->id] : false;
    }

    public function getSearchAttributes(): array
    {
        // Any readable property: a column, a translated or custom attribute, or a getter.
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
`SearchableInterface`, and the two traits implement the rest of the interface — `SearchableInterface` extends
`Models\Interfaces\AdminModelInterface`, which is where the hit's route, title, badge and icon come from. The values are read through the magic getter, so a name may be a
column, a translated or custom attribute, or a plain getter — the media `File` indexes `filename`, which is
`getFilename()`. Register
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
- **InnoDB indexes neither its built-in stopwords nor tokens shorter than `innodb_ft_min_token_size` (3)**, and
  its parser splits `f2a@domain.com` into `f2a`, `domain` and `com` — so `com`, `IT` and `.de` were never in the
  index, and no query could reach them. A query term is never the problem: a prefix term is always matched
  literally, whatever its length. `Search\SearchText::getIndexTokens()` therefore appends a `__`-prefixed copy
  of every dropped token to the indexed content, and `toBooleanQuery()` asks for one as `+(__com* com*)`: the
  copy finds `domain.com`, the plain prefix still finds `commerce`. The underscore is a word character to
  InnoDB's parser and counts towards the minimum length, so `__i` is indexed. Changing
  `SearchText::UNINDEXED_PREFIX` or `STOPWORDS` needs a `search/rebuild`.
- **A stopword is required on its own and optional beside another token.** `the` alone or `.com` alone has to
  match, since nothing else narrows; in `the Bergfirma` or `firma.de` it only ranks, so a record without it is
  still found. A short token that is not a stopword (`AG`, `VW`) is always required; `IT` is a stopword, `it`
  is on InnoDB's list. The optional pair is emitted flat (`+firma* __de* de*`), because InnoDB ORs a
  parenthesised group without an operator with the whole query. A project that controls
  the server can instead set `innodb_ft_server_stopword_table` to an empty table and `innodb_ft_min_token_size`
  to 1, rebuild the index and leave the prefixed copies unused.
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
