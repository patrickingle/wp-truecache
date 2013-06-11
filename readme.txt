=== WP-TrueCache ===
Version: 
Contributors: phkcorp2005
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9674139
Tags: Cache, Memcache, SuperCache, TotalCache, Real Cache, File based caching
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 3.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A plugin that properly enables Wordpress in a high available, cluster environment using Memcache with File caching failover.

== Description ==

 A plugin that properly enables Wordpress in a high available, cluster environment using Memcache with File caching failover. No need for separate caching plugins. This plugin handles all the caching and works with CDN. Includes filters to prevent caching on certain pages, users, etc. Detail documentation on the Admin dashboard. Multisite enabled.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Make sure that the PHP Memcache extension is installed. Use PHP Info to verify.
3. Edit wp-config.php and add the following lines just above /* That's all, stop editing! Happy blogging. */
define('WP_MEMCACHE_SERVERS','[replace with an actual memcache IP address]');
define('WP_MEMCACHE_PORT',11211);
define('WP_CACHE',true);
include(ABSPATH."wp-content/plugins/wp-truecache/config.php");
4. Now active the plugin from the Admin dashboard.
5. See the side bar menu.

== Frequently asked questions ==

= A question that someone might have =

An answer to that question.

== Screenshots ==

[Main Admin Dashboard](http://www.flickr.com/photos/97331227@N06/9015426323/) 
[Architecture Page](http://www.flickr.com/photos/97331227@N06/9016617426/)

== Changelog ==

= 2.0.6 =
* Initial Public Release