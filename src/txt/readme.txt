=== SkautIS integration ===
Contributors: skaut, davidulus, marekdedic, kalich5
Tags: SkautIS, login, registration, skaut
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.30
License: GPLv3 or later
License URI: https://github.com/skaut/skautis-integration/blob/master/LICENSE

Login, registration, user management and other features connecting SkautIS to WordPress.

== Description ==

Login, registration, user management and other features connecting SkautIS to WordPress.

=== Features ===
* Login to WordPress with a SkautIS account
* Manage SkautIS account connections and import users to your site
* Enable registration of users to your site based on their SkautIS functions, memberships or other criteria

=== Minimal requirements ===
- WordPress 4.9.6 or higher
- PHP 7.4 or higher

=== GitHub ===
All the sources for the plugin and the build process are detailed in our [GitHub repo](https://github.com/skaut/skautis-integration/).

== Installation ==
1. Download and install the plugin from the WordPress plugin directory or from [GitHub](https://github.com/skaut/skaut-google-drive-gallery/releases)
2. Activate the plugin
3. Request an APP ID for SkautIS by following the [docs](https://napoveda.skaut.cz/skautis/skautis-integration) (only in Czech)
4. In the admin interface, open the plugin settings, enter the provided APP ID and register the plugin
5. The plugin is now fully functional

== Frequently Asked Questions ==

= How to configure this plugin? =
See our [documentation](https://napoveda.skaut.cz/skautis/skautis-integration) (only in Czech).

== Screenshots ==


== Changelog ==

= 1.1.30 =
* Fixed errors in rule editor

= 1.1.29 =
* Support for WordPress 6.6
* DataTables updates
* Fixed possible clashes with other plugins

= 1.1.28 =
* Fixed page visibility rules not being saved

= 1.1.27 =
* Fixed role switcher on the user management page
* Fixed an error on PHP 7

= 1.1.26 =
* Security fixes
* Improved error handling
* Improved translations
* Enabled asset minification, leading to faster loading
* Improved asset caching, leading to faster loading
* Fixed corner-case issues in rule processing

= 1.1.25 =
* Security fixes

= 1.1.24 =
* Added support for PHP 8.1
* Raised minimum PHP version to 7.4

= 1.1.23 =
* Optimized dependency loading
* Fixed a typo in code

= 1.1.22 =
* Fixed an issue where the plugin would cause a fatal error on yet some other sites

= 1.1.21 =
* Fixed an issue from 1.1.19 where the plugin would cause a fatal error on some sites

= 1.1.20 =
* Re-released version 1.1.18

= 1.1.19 =
* Switched from session to WP transients for Skautis user management

= 1.1.18 =
* Fixed security issues
* Replaced dependencies loaded from CDN with bundled ones

= 1.1.17 =
* oprava chyb

= 1.1.16 =
* oprave kompatibility s PHP 8.x

= 1.1.15 =
* Nejnižší požadovaná verze změněna na 4.9.6

= 1.1.14 =
* Ve výběru rolí na stránce "Správa uživatelů" se nyní zobrazují jen aktivní role podle skautISu

= 1.1.13 =
* Oprava zpracování funkcí při přihlášení/registraci

= 1.1.12 =
* Nové pravidlo: kvalifikace
* Pravidla: u členství, rolí a funkcí lze nyní zvolit možnost "jakékoliv" u evidenčního čísla jednotky
* Správa uživatelů - vyšší výchozí počet záznamů na stránku, ukládání stavu tabulky = po znovunačtení stránky se tabulka zobrazí v původním stavu
* Modul Viditelnost - pravidla převzatá z nadřazených stránek se u podřízených stránek označí ve výběru jako vybraná (disabled)
* Aktualizace JS knihoven

= 1.1.11 =
* Opravy chyb

= 1.1.10 =
* Novinka - na podřízené stránky se nyní použijí pravidla z celého hierarchického stromu nadřízených stránek, nejen z nejvýše nadřazené stránky
* Výpis pravidel z nadřazených stránek
* Drobné opravy

= 1.1.9 =
* Při použití pravidla "Všichni bez omezení" se mohou nově přihlašovat jen ti, kteří mají propojený účet ve skautISu. Aby se zabránilo tomu, že si někdo vytvoří jen tak účet a pak se přihlásí třeba do uzavřené sekce nějakého skautského webu.

= 1.1.8 =
* Oprava logování přihlášení/odhlášení pro pluginy, které hlídají co se na webu děje. (př.: Simple History)

= 1.1.7 =
* Změna rozesílání emailů, nyní se odesílají vždy podle globálního nastavení

= 1.1.6 =
* Opraveno vytváření uživatelů

= 1.1.5 =
* Opraveno zobrazování stránky se správou uživatelů při zvolení špatné role

= 1.1.4 =
* změna vytváření uživatelského jména při registraci - místo emailu uživatele se nyní nastaví jeho login do skautISu
* oprava propojování účtů
* oprava načítání query editoru
* oprava ukládání nastavení na PHP 7.0.x

= 1.1.3 =
* možnost vytvářet manuálně nové uživatele
* lepší vyhledávání na stránce "Správa uživatelů" (řeší omezení limitu 500 uživatelů naráz ze skautISu)

= 1.1.2 =
* opravy textů v pluginu

= 1.1.1 =
* dopnění překladů, frontend je nyní AJ a CZ

= 1.1 =
* vyžadováno PHP 7.0 a vyšší
* nové pravidlo: funkce
* viditelnost stránek/příspěvků/custom type
* shortcode pro vymezení obsahu
* lepší rozhraní na stránce "Správa uživatelů"

= 1.0 =
* podpora přihlášení přes skautIS
* podpora registrace přes skautIS
* nastavování pravidel
* propojování již registrovaných uživatelů
* nastavování propojování pravidel a rolí ve WordPressu
* vyžadován WordPress 4.8 a vyšší
