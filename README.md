wp-truecache
============

True Caching for Wordpress which implements both file-based and distributive memcache caching mechanisms.

WP-TrueCache is the first caching plugin that correctly implements Memcache with a File-base caching failover and works for multisite installations. WP-TrueCache took over 9 months to develop, to resolve CDN, Comment author, Admin, and no-cache issues. WP-TrueCache uses page buffering to cache pages to Memcache (and file-based). Some of the best features of Total Cache, SuperCache, and Memacache were combined. Using WP-TrueCache has reduce page load times down to 72 milliseconds from the common 3 second page load using Memcache/File-based, using JMeter. When WP-TrueCache occurs no database activity should be present.


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
Amazon at http://www.amazon.com/dp/B00RAIMGAC 

High Availability means for a system to be readily available, but in today’s market and industry, there is more to being just available and needs to be quantified. High Availability have been referred to as failover techniques for downtime of hardware and introduced load balancing to relieved traffic congestion, as well as database replication for data loss prevention. A clearer definition of HA is for a website to continuously serve an unlimited number of visitors in the most efficient manner possible. High Availability configuration must also be scalable where as the visitor count increases, the configuration can be expanded to handle the increase load seamlessly to the visitor. 

To achieve high availability you need to balance the load produced by the visitor traffic to your website, replicate the content for full or partial downtime, recover the content on restoration (full or partial). Before load balancing, database replication and hardware failover has been sufficient, but as web services become more complex, an additional component is coming into view, that is a distributive caching mechanism. But what happens when your HA configuration fails to perform even when all tests and measurements assure you of it’s success? What do you do? 

Wordpress is one of those web application frameworks that is so complex, that traditional methods of load balancing and failover are insufficient. This document attempts to discover, uncover and implement strategies, processes and technologies that meet both current and future demands for High Availability. 

<img src="http://ecx.images-amazon.com/images/I/51NqitVDtOL._BO2,204,203,200_PIsitb-sticker-v3-big,TopRight,0,-55_SX278_SY278_PIkin4,BottomRight,1,22_AA300_SH20_OU01_.jpg">

Ask me how you can get this book for Free?

Videos
======

Introduction to True Cache plugin

[![True Cache Plugin for Wordpress](https://www.youtube.com/watch?v=rmBLEJbE3SE)](https://www.youtube.com/watch?v=rmBLEJbE3SE)



