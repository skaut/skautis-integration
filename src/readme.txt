=== skautIS integration ===
Contributors: skaut, davidulus, marekdedic, kalich5
Tags: skaut, multisite, plugin, shortcode, skautIS, registrace
Requires at least: 5.0
Tested up to: 5.8
Requires PHP: 7.0
Stable tag: 1.1.22
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Implementation of login, registration and other services from the skautIS information system to WordPress.

== Description ==

<h2>Minimal requirements</h2>
- WordPress 4.9.6 and higher
- PHP 7.0 and higher

Implementation of login, registration and other services from the skautIS information system to WordPress.

Plugin after activation will require APP ID, without the plugin will not work at all. Instructions on how to set up the plugin and get the APP ID can be found in [help (Czech)](https://napoveda.skaut.cz/skautis/skautis-integration)

**GITHUB**
[https://github.com/skaut/skautis-integration/](https://github.com/skaut/skautis-integration/)

== Installation ==
1. Download the plugin and activate
2. The skautIS item appears in the left menu
3. You need to request the APP ID guide is in [help (Czech)](https://napoveda.skaut.cz/skautis/skautis-integration)
4. You enter the APP ID and the plugin is fully activated

== Frequently Asked Questions ==
**How to set up the plugin correctly?**
[help (Czech)](https://napoveda.skaut.cz/skautis/skautis-integration)

== Screenshots ==


== Changelog ==

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
* error correction

= 1.1.16 =
* PHP 8.x compatibility fix

= 1.1.15 =
* Lowest required version changed to 4.9.6

= 1.1.14 =
* Only active roles according to skautIS are now displayed in the role selection on the "User management" page

= 1.1.13 =
* Correction of function processing during login / registration

= 1.1.12 =
* New rule: qualifications
* Rules: for memberships, roles and functions, you can now select "any" for the unit registration number
* User management - higher default number of records per page, saving the state of the table = after reloading the page, the table will be displayed in its original state
* Visibility module - rules taken from parent pages are marked as selected for children in the selection (disabled)
* Update JS libraries

= 1.1.11 =
* error correction

= 1.1.10 =
* New - rules from the entire hierarchical tree of parent pages, not just the top parent page, are now applied to child pages
* Listing rules from parent pages
* Minor repairs

= 1.1.9 =
* When using the "Everyone without restrictions" rule, only those who have a linked account in scoutIS can log in now. In order to prevent someone from just creating an account and then logging in to a closed section of a scout website, for example.

= 1.1.8 =
* Fixed login / logout logging for plugins that monitor what's happening on the site. (ex: Simple History)

= 1.1.7 =
* Changing the sending of emails, now they are always sent according to the global settings

= 1.1.6 =
* Fixed user creation

= 1.1.5 =
* Fixed displaying the user management page when selecting the wrong role

= 1.1.4 =
* Change of user name creation during registration - instead of the user's email, his login to skautIS is now set
* Account linking fix
* Fix loading query editor
* Fix saving settings on PHP 7.x

= 1.1.3 =
* Possibility to create new users manually
* Better search on the "User management" page (solves the limit of the limit of 500 users at a time from scoutIS)

= 1.1.2 =
* Text corrections in the plugin

= 1.1.1 =
* Addition of translations, the frontend is now AJ and CZ

= 1.1 =
* Required PHP 7.0 and higher
* New rule: function
* Visibility of pages / posts / custom type
* Shortcode for content definition
* Better interface on the "User Management" page

= 1.0 =
* Support for logging in via skautIS
* Support for registration via skautIS
* Setting rules
* Connecting already registered users
* Setting up linking rules and roles in WordPress
* Required WordPress 4.8 and higher