=== skautIS integrace ===
Contributors: davidulus, skaut, kalich5
Tags: skaut, multisite, plugin, shortcode, skautIS, registrace
Requires at least: 4.8
Tested up to: 4.9
Requires PHP: 7.0
Stable tag: 1.1.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Implementace přihlašování, registrace a dalších služeb z informačního systému skautIS do WordPressu.

== Description ==

<h2> Minimální požadavky</h2>
- WordPress 4.8 a vyšší
- PHP 7.0 a vyšší

Implementace přihlašování, registrace a dalších služeb z informačního systému skautIS do WordPressu.

Plugin po aktivaci bude vyžadovat APP ID, bez toho nebude plugin vůbec fungovat. Návod jak nastavit plugin a zístat APP ID  najdete v [nápovědě](https://napoveda.skaut.cz/skautis/skautis-integration)

**Jsme na GitHubu**
[https://github.com/skaut/skautis-integration/](https://github.com/skaut/skautis-integration/)

== Installation ==
1. Stáhnout si plugin a aktivovat
2. V levém menu se objeví položka skautIS
3. Musíte si zažádat o APP ID návod je v [nápovědě](https://napoveda.skaut.cz/skautis/skautis-integration)
4. Zadáte APP ID a plugin se plně aktivuje

== Frequently Asked Questions ==
**Jak plugin správně nastavit?**
[Nápověda](https://napoveda.skaut.cz/skautis/skautis-integration)

== Screenshots ==


== Changelog ==
= 1.x =
* nové pravidlo: účastník akce

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
