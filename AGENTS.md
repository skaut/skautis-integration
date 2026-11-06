# AGENTS.md

This file provides guidance to coding agents when working with code in this repository.

## Overview

WordPress plugin that lets users log in to and register on a WordPress site through skautIS, the information system of Junák – český skaut. It also restricts content visibility based on skautIS roles, memberships, functions, and qualifications. User-facing strings are in English and are translated on translate.wordpress.org; the Czech translation is kept in `languages/skautis-integration-cs_CZ.po` for importing it there (it is not part of the built plugin). The README is in Czech. The text domain is `skautis-integration`. Supported floors: PHP 8.3, WordPress 7.0, and JS/CSS at ES2017 / `baseline 2019`.

## Commands

Setup: `composer install && npm ci`

- `npm run build` runs gulp: it cleans `dist/`, then builds everything into `dist/`, which is the installable plugin.
- `npm run lint` runs every linter in parallel: ESLint, stylelint, `tsc --noEmit`, phpcs, phpmd, phpstan (level 8), and phan.
  - To run one linter: `npm run lint:php:phpstan`, `npm run lint:eslint`, `npm run lint:php:phpcs`, and so on. See `package.json`.
  - **phan and phpstan need `dist/vendor` to exist**, so run `npm run build` before PHP linting.
- `npm run check` checks the built `dist/` for ES2017 and CSS compatibility (es-check, `scripts/check-css-compat.js`).
- `npm run plugin-check` runs the WordPress Plugin Check against `dist/` inside `wp-env` (needs Docker).
- There is no test suite.

## Build layout (src → dist)

`src/` is split by file type, and `gulpfile.js` maps each type into the runtime layout under `dist/`:

- `src/php/**` → `dist/` (PHP keeps its structure, so `src/php/src/admin/…` becomes `dist/src/admin/…`)
- `src/ts/<area>/*.ts` → `dist/<area>/js/*.min.js`. Each file is compiled on its own by `gulp-typescript` and minified by terser. There is no bundler and there are no imports between files. Globals come from WordPress, jQuery, and the bundled libraries, and are typed in `src/d.ts/`.
- `src/css/<area>/*.css` → `dist/<area>/css/*.min.css` (lightningcss)
- `src/png/**` → `dist/src/**`, and `src/txt/*` → `dist/` (`readme.txt` is the wordpress.org readme and changelog)
- Scripts that use `wp.i18n` are also shipped unminified (see the Visibility admin gulp task), because wordpress.org extracts strings only from non-`.min.js` files; WordPress loads the translations of `foo.min.js` by the name `foo.js`. Register them with `wp_set_script_translations()`. The bundled jQuery QueryBuilder translations are only loaded for Czech (`Helpers::is_czech_locale()`).
- npm frontend libraries (jQuery QueryBuilder, select2, interact.js, jquery.repeater, Font Awesome) are copied into `dist/bundled/`.
- The block editor script (`src/ts/modules/Visibility/admin/skautis-modules-visibility-block-editor.ts`) uses the global `wp` object without JSX (`wp.element.createElement`). The used parts of the WordPress packages are typed in `src/d.ts/wordpress.d.ts`.
- Composer dependencies (mainly `skautis/skautis`) are prefixed with **PHP-Scoper** to the `Skautis_Integration\Vendor\` namespace, in `dist/vendor/`. `gulpfile.js` also patches the composer autoloader. In plugin code, reference the SkautIS library as `Skautis_Integration\Vendor\Skautis\…`.

When you add a PHP, TS, or CSS file under a new area, you may need a new gulp task. New PHP class files also need an explicit `require` in `src/php/class-skautis-integration.php`, because there is no autoloader for plugin code.

## PHP architecture

- Entry point: `src/php/skautis-integration.php` defines the constants and instantiates `Skautis_Integration` (`class-skautis-integration.php`). That class requires every file, then boots the services.
- `src/php/src/services/class-services.php` is a hand-written service locator made of static lazy singletons. It is the single place where dependencies are wired: the constructor injection happens here. Boot order: `get_general()` (actions and rules post type), then `get_admin()` or `get_frontend()` depending on `is_admin()`, then `get_features()` (Visibility always, the Register admin settings in the admin, and Register itself only when registration is enabled).
- `auth/`: `Skautis_Gateway` wraps the scoped SkautIS client. The app ID and test/prod environment come from options. `Skautis_Login`, `WP_Login_Logout`, and `Connect_And_Disconnect_WP_Account` handle the login flows.
- `general/class-actions.php` registers the rewrite rules `skautis/auth/<action>` and the configurable login page (default `skautis/prihlaseni`). It dispatches those requests through query vars. The plugin flushes rewrite rules when the `skautis_rewrite_rules_need_to_flush` option is set.
- **Rules** (`rules/`): a custom post type edited in the admin with jQuery QueryBuilder (`src/ts/rules/admin/`). Each rule block implements `rules/interface-rule.php` (Role, Membership, Func, Qualification, All). `Rules_Manager` checks whether the current skautIS user passes a rule. Rule blocks can be extended through the `skautis-integration_rules` filter.
- **Register and Visibility** (`modules/`): the two features, each with its own `admin/` and `frontend/` subfolders. They used to be modules that had to be activated; now Visibility is always on and registration is turned on by the `skautis-integration_registration_enabled` option ('1' or '0', on the Registration settings page). `Services::is_registration_enabled()` checks it. `Skautis_Integration::maybe_migrate_modules()` converts the old `skautis_integration_activated_modules` option once. The option names keep the old `_modules_` prefix so that existing settings stay.
- **Visibility**: rules for a whole post are stored in post meta (`skautis-integration_rules`, `_rules_includeChildren`, `_rules_visibilityMode`), edited in a block editor sidebar panel (`Block_Editor`) or in the classic editor metabox. Any block can have rules in its `skautisRules` and `skautisVisibilityMode` attributes; `Block_Visibility` hides such blocks in `render_block` and in generated excerpts.
- **Settings page**: one page (`admin.php?page=skautis-integration`) with tabs General / Login / Registration / Content visibility, built in `Admin\Settings` (`TABS` maps each tab to its own settings group, so saving one tab never touches the others). Register and Visibility only add their sections and fields to their groups; sections are shown as cards through `Settings::get_card_section_args()`. Use `Settings::is_settings_tab()` to enqueue assets for one tab. The header and tabs follow the core Privacy and Site Health screens; old submenu URLs are redirected to the tabs in `redirect_old_pages()`.
- **User management page** (`Admin\Users_Management`): uses the same header (`Settings::print_page_header()`) and cards. The skautIS users table is plain markup (`print_users_table()` takes the data, so it can be rendered without skautIS); `src/ts/admin/skautis-admin-users-management.ts` filters it and moves the single connect panel below the selected row. No table or modal library is used.
- Namespaces follow the directories under `Skautis_Integration\` (for example `Skautis_Integration\Modules\Register`). File names follow the WordPress convention (`class-foo-bar.php` holds `Foo_Bar`), which phpcs enforces with WPCS.

## Conventions

- Version bumps must stay in sync across `package.json`, `src/php/skautis-integration.php` (header `Version:` and the `SKAUTIS_INTEGRATION_VERSION` constant), and `src/txt/readme.txt` (`Stable tag` plus a changelog entry).
- Pushing a tag triggers `.github/workflows/release.yml`, which builds the plugin, deploys it to the wordpress.org SVN, and creates a GitHub release with the changelog taken from `readme.txt`.
- `APP ID - povolení služeb.md` lists the skautIS web-service permissions that each plugin version needs. Update it when code starts calling a new skautIS API method.
