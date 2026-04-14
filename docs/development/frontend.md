---
order: 3
---

# Frontend

You can find frontend files under `src/frontend`.
The frontend is built with Vue.js.
After first run with `docker-compose up` dependencies will be installed automatically.
If you want to install dependencies manually, you can run `npm install` under `src/frontend` folder.

## Folder Structure

When adding new files to the project, please adhere to the following folder structure:

- **Creating New Modules:**
  Modules are the components used in pages.
  For example, the Client module holds components related to clients.
  Every component associated with clients should be placed under the Client module.

```sh
src/frontend
├── src
│   ├── modules
│   │   ├── newModule
```

- **Creating New Pages:**
  Pages are the components used in routes.
  We follow the nuxt.js folder structure for pages.
  The `index.vue` file under a page folder represents the listing page of the page, while the `_id.vue` file represents the detail page.
  Since we are not using nuxt.js, routes need to be defined manually.
  You can find the routes in the `src/frontend/src/ExportedRoutes.js` file.

```sh
src/frontend
├── src
│   ├── pages
│   │   ├── newPage
|   |   |   ├── index.vue
|   |   |   ├── _id.vue
```

## Translations (i18n)

The frontend uses [vue-i18n](https://kazupon.github.io/vue-i18n/) for internationalization.
All user-facing text should come from translation files rather than being hardcoded.

### How it works

Translation strings live in JSON files under `src/frontend/src/assets/locales/`.
Each supported language has its own file (`en.json`, `fr.json`, `pt.json`, etc.).
These are imported in `src/frontend/src/i18n.js` and registered with vue-i18n.
The user's selected language is persisted in `localStorage` and can be changed from **Settings → Configuration → Language**.

### File structure at a glance

```sh
src/frontend/src/
├── i18n.js                          # vue-i18n setup, imports all locale files
├── bootstrap.js                     # VeeValidate dictionary (form validation messages)
├── assets/locales/
│   ├── en.json                      # English (source of truth)
│   ├── fr.json                      # French
│   ├── pt.json                      # Portuguese
│   ├── ar.json                      # Arabic
│   └── bu.json                      # Burmese
└── modules/Settings/Configuration/
    └── MainSettings.vue             # language selector dropdown
```

### Translation key format

The JSON files use a flat-ish structure with four top-level sections:

| Section    | Purpose                                            | Example key                               |
| ---------- | -------------------------------------------------- | ----------------------------------------- |
| `errors`   | Error messages shown to the user                   | `errors.alreadyUsedCompanyEmail`          |
| `menu`     | Navigation menu labels (includes `subMenu`)        | `menu.Dashboard`, `menu.subMenu.Settings` |
| `messages` | Toast / notification messages                      | `messages.successfullyCreated`            |
| `phrases`  | Longer UI labels, descriptions, multi-part strings | `phrases.newAgent`                        |
| `words`    | Single words reused across the UI                  | `words.save`                              |

**Pluralization and multi-part strings** use the pipe (`|`) separator:

```json
"appliance": "Appliance | Appliances"
"addReceipt": "Add Receipt | Receipt Added successfully | This agent does not owe the energy provider. "
```

**Interpolation** uses curly braces:

```json
"paymentAmountCannotBeLess": "Payment amount cannot be less than {amount}"
```

### Using translations in Vue components

```vue
<!-- Simple key -->
<span>{{ $t('words.save') }}</span>

<!-- With interpolation -->
<span>{{ $t('messages.paymentAmountCannotBeLess', { amount: 100 }) }}</span>

<!-- Pluralization (0 = first form, 1 = second form, etc.) -->
<span>{{ $tc('words.appliance', 1) }}</span>
<!-- "Appliance" -->
<span>{{ $tc('words.appliance', 2) }}</span>
<!-- "Appliances" -->
```

In JavaScript:

```js
this.$t("phrases.somethingWentWrong")
this.$tc("words.meter", 2)
```

### How to update existing translations

When you add a new feature or change existing UI text, you need to update **all** locale files:

1. **Add or modify the key in `en.json` first.** English is the source of truth. Pick the appropriate section (`words` for single reusable words, `phrases` for longer UI strings, `messages` for notifications, `menu` for navigation).

2. **Add the same key to every other locale file** (`fr.json`, `pt.json`, `ar.json`, `bu.json`). If you don't know the correct translation, add the English text as a placeholder and flag it for a translator — an English fallback is better than a missing key that shows a raw key string in the UI.

### Sorting translation files

After adding or renaming translation keys, run the sorting script to keep JSON files consistently ordered across locales.

```sh
sh tools/sort_lang_json.sh src/frontend/src/assets/locales
```

This requires `jq` to be installed.

## MPM Brand guidelines

To ensure a consistent look and feel across our applications, we maintain a set of brand guidelines covering typography, color palettes, iconography, and spacing.

When designing any user-facing UI elements, please consult the [MPM Brand guidelines](https://drive.google.com/file/d/1ZBszNtIvf_iNjUou7S3A_9swcIHBr37-/view?usp=sharing) for styling direction.
If you encounter a scenario not covered by the guidelines, feel free reach out!

[![MPM Brand guidelines](/screenshots/MPM_Brand_Guidelines.png)](https://drive.google.com/file/d/1ZBszNtIvf_iNjUou7S3A_9swcIHBr37-/view?usp=sharing)
