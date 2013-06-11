wp-truecache
============

True Caching for Wordpress which implements both file-based and distributive memcache caching mechanisms

WP-CONFIG Minimum Additions
===========================

The required minimum additions are needed in your wp-config.php before you can activate WP-TrueCache plugin.

<code>
<br>
define('WP_MEMCACHE_SERVERS','[replace with an actual memcache IP address]');<br>
define('WP_MEMCACHE_PORT',11211);<br>
define('WP_CACHE',true);<br>
include(ABSPATH."wp-content/plugins/wp-truecache/config.php");<br>
</code>

Screenshots
===========

<img src="WP-TrueCache-Dashboard-Pg01.png"><br/>
<img src="WP-TrueCache-Dashboard-Pg02.png"><br/>
