# Upgrade Guide

## 3.0.0 — Key-based translations via the `Lang` facade

v3 replaces direct `Yii::t()` calls with the `Hirtz\Skeleton\I18n\Lang` facade and switches every
platform message source to **key-based translations** resolved with `forceTranslation => true`.
Instead of the English sentence being both the lookup key and the fallback, each string now has a
stable, uppercase, domain-first **key**, and the English text lives in the `en-US` message file like
any other language.

```php
// before
Yii::t('cms', 'Create Entry');

// after
Lang::t('cms', 'ENTRY_CREATE_BUTTON');
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
the mechanical `Yii::t` → `Lang::t` swap is trivial, but no tool can reliably invent semantic keys
from arbitrary English, so the key assignment is manual (best done per model/feature).

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
   use Hirtz\Skeleton\I18n\Lang;

   Lang::t('app', 'PRODUCT_NAME_LABEL');
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

`Yii::t()` still works (the facade delegates to it), so a partially migrated app runs correctly:
un-migrated calls keep passing the English string, which `forceTranslation` returns unchanged when no
key matches. Migrate incrementally, one category or feature at a time.
