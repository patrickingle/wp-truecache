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

<a href="http://www.flickr.com/photos/97331227@N06/9015426323/" title="WP-TrueCache-Dashboard_Pg01 by inglepatrick, on Flickr"><img src="http://farm3.staticflickr.com/2861/9015426323_f009a8e817_n.jpg" width="320" height="255" alt="WP-TrueCache-Dashboard_Pg01"></a>
<br/>
<a href="http://www.flickr.com/photos/97331227@N06/9016617426/" title="WP-TrueCache-Dashboard_Pg02 by inglepatrick, on Flickr"><img src="http://farm9.staticflickr.com/8399/9016617426_88f1959af8_n.jpg" width="320" height="210" alt="WP-TrueCache-Dashboard_Pg02"></a>

Wordpress Plugin Package
========================
The zip file, wp-truecache.zip is wordpress-ready to install.

Publication
===========

The following publication, "Wordpress High Availability: Configuration, Deployment, Maintenance Tips & Techniques" available on
Amazon at http://www.amazon.com/dp/B00RAIMGAC shows how to implement response design in your wordpress theme using both bootstrap and foundation javascript libraries.


<img src="http://ecx.images-amazon.com/images/I/51NqitVDtOL._BO2,204,203,200_PIsitb-sticker-v3-big,TopRight,0,-55_SX278_SY278_PIkin4,BottomRight,1,22_AA300_SH20_OU01_.jpg">

Ask me how you can get this book for Free?


