wp-truecache
============

True Caching for Wordpress which implements both file-based and distributive memcache caching mechanisms

WP-CONFIG Minimum Additions
===========================

The required minimum additions are needed in your wp-config.php before you can activate WP-TrueCache plugin.


define('WP_MEMCACHE_SERVERS','[replace with an actual memcache IP address]');
define('WP_MEMCACHE_PORT',11211);
define('WP_CACHE',true);
include(ABSPATH."wp-content/plugins/wp-truecache/config.php");
