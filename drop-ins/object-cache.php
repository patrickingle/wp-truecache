<?php
/*
Plugin Name: WPTRUECACHE Object Cache
Description: WPTRUECACHE backend for the WP Object Cache.
Version: 1.0.0
Plugin URI: http://github.com/patrickingle/wp-truecache
Author: Patrick Ingle

Install this file to wp-content/object-cache.php
*/

define ( 'WPTRUECACHE_OBJECT_CACHE_INSTALLED', 'YES' );

if (file_exists(dirname(__FILE__).'/../filecache.php')) {
	include (dirname(__FILE__).'/../filecache.php');
}

function wptruecache_cache_add($key, $data, $flag = '', $expire = 0) {
	global $wptruecache_object_cache;

	return $wptruecache_object_cache->add($key, $data, $flag, $expire);
}

function wptruecache_cache_incr($key, $n = 1, $flag = '') {
	global $wptruecache_object_cache;

	return $wptruecache_object_cache->incr($key, $n, $flag);
}

function wptruecache_cache_decr($key, $n = 1, $flag = '') {
	global $wptruecache_object_cache;

	return $wptruecache_object_cache->decr($key, $n, $flag);
}

function wptruecache_cache_close() {
	global $wptruecache_object_cache;

	if (is_object($wptruecache_object_cache))
		return $wptruecache_object_cache->close();

	return FALSE;
}

function wptruecache_cache_delete($id, $flag = '') {
	global $wptruecache_object_cache;

	return $wptruecache_object_cache->delete($id, $flag);
}

function wptruecache_cache_flush() {
	global $wptruecache_object_cache;

	return $wptruecache_object_cache->flush();
}

function wptruecache_cache_get($id, $flag = '') {
	global $wptruecache_object_cache;

	return $wptruecache_object_cache->get($id, $flag);
}

function wptruecache_cache_init() {
	global $wptruecache_object_cache;

	$wptruecache_object_cache = new WPTRUECACHE_Object_Cache();
}

function wptruecache_cache_replace($key, $data, $flag = '', $expire = 0) {
	global $wptruecache_object_cache;

	return $wptruecache_object_cache->replace($key, $data, $flag, $expire);
}

function wptruecache_cache_set($key, $data, $flag = '', $expire = 0) {
	global $wptruecache_object_cache;

	if ( defined('WP_INSTALLING') == false )
		return $wptruecache_object_cache->set($key, $data, $flag, $expire);
	else
		return $wptruecache_object_cache->delete($key, $flag);
}

function wptruecache_cache_add_global_groups( $groups ) {
	global $wptruecache_object_cache;

	$wptruecache_object_cache->add_global_groups($groups);
}

function wptruecache_cache_add_non_persistent_groups( $groups ) {
	//global $wp_object_cache;

	//$wp_object_cache->add_non_persistent_groups($groups);
	return;
}

function wptruecache_cache_connection_count() {
	global $wptruecache_object_cache;
	
	return $wptruecache_object_cache->connection_count;
}

function wptruecache_cache_get_source() {
	global $wptruecache_object_cache;
	
	return $wptruecache_object_cache->cache_source;
}

function get_memcache_server_list() {
	$mcservers = array();
	
	if (defined('WP_MEMCACHE_FULL_SERVER_LIST')) {
		$servers = explode("|",WP_MEMCACHE_FULL_SERVER_LIST);
		$i=0;
		foreach($servers as $server) {
			$temp = explode(",",$server);
			if (count($temp) == 2) {
				$mcservers[$i]['host'] = $temp[0];
				$mcservers[$i]['port'] = $temp[1];
				$i++;		
			}
		}
	} else {
		// WP_MEMCACHE_SERVERS constant defined in WP-CONFIG.PHP
		if (defined('WP_MEMCACHE_SERVERS')) {
			$servers = explode(",",WP_MEMCACHE_SERVERS);
			$port = WPTRUECACHE_MEMCACHE_PORT;
			if (defined('WP_MEMCACHE_PORT')) $port = WP_MEMCACHE_PORT;
			if (count($servers) > 1) {
				$i=0;
				foreach($servers as $server) {
					$mcservers[$i]['host'] = $server;
					$mcservers[$i]['port'] = $port;
					$i++;
				}
			} else {
				$mcservers[0]['host'] = $servers[0];
				$mcservers[0]['port'] = $port;
			}
		} else {
		}
	}

	$results = array();
	
	if (extension_loaded("memcache")) {
		$i=0;
		foreach ($mcservers as $mcserver) {
			$tempmc = new Memcache();
			// adding '@' prefix the function call to suppress unwarranted console errors (like memcache.connect error).
			$rc = @$tempmc->connect($mcserver['host'],$mcserver['port']);
			if (defined('WP_TRUECACHE_TRACEON')) @header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i++."]: Memcached server ".$mcserver['host']." is ".($rc ? "CONNECTED" : "NOT CONNECTED"));
			if ($rc !== FALSE) {
				$results[] = $mcserver;
				@$tempmc->close();
			}	
			$i++;
		}
	}
		
	return $results; // $mcservers;	
}

function get_memcache_connected_object($mcservers) {
	$mcobjs = array();
	
	if (extension_loaded("memcache")) {
		$i=0;
		foreach ($mcservers as $mcserver) {
			$tempmc = new Memcache();
			// adding '@' prefix the function call to suppress unwarranted console errors (like memcache.connect error).
			$rc = @$tempmc->connect($mcserver['host'],$mcserver['port']);
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime()."_".__LINE__."-".$i++."]: Memcached server ".$mcserver['host']." is ".($rc ? "CONNECTED" : "NOT CONNECTED"));
			if ($rc !== FALSE) {
				$mcobjs[] = $tempmc;
			}
		}
	} else {
		error_log("WPTRUECACHE-HA Error: memcache extension is not loaded. No memcache caching is present. Check your php.ini",0);
	}

	return $mcobjs;
}

function close_memcache_connected_object($mcobjs) {
	if (is_array($mcobjs)) {
		foreach ($mcobjs as $mcobj) {
			$mcobj->close();
		}
	}
}

function delete_from_memcache_connected_object($key) {	
	$mcservers = get_memcache_server_list();
	$mcobjs = get_memcache_connected_object($mcservers);
	
	if (is_array($mcobjs)) {
		foreach ($mcobjs as $mcobj) {
			$mcobj->delete($key);
		}
	}
	
	close_memcache_connected_object($mcobjs);
}

class WPTRUECACHE_Object_Cache {

	// Memcache 
	var $mc = array();
	var $memcache_pool = array();
	var $memcache_connections = 0;
	var $cache_source = "FROM_INTERNAL_MEMORY"; // options: FROM_INTERNAL_MEMORY, FROM_FILE_CACHE, FROM_MEMCACHE, FROM_DATABASE

	// Filecache 
	var $fc = null;

	/**
	 * Holds the cached objects
	 *
	 * @var array
	 * @access private
	 * @since 2.0.0
	 */
	var $cache = array ();

	/**
	 * The amount of times the cache data was already stored in the cache.
	 *
	 * @since 2.5.0
	 * @access private
	 * @var int
	 */
	var $cache_hits = 0;

	/**
	 * Amount of times the cache did not have the request in cache
	 *
	 * @var int
	 * @access public
	 * @since 2.0.0
	 */
	var $cache_misses = 0;

	/**
	 * List of global groups
	 *
	 * @var array
	 * @access protected
	 * @since 3.0.0
	 */
	var $global_groups = array();

	/**
	 * Adds data to the cache if it doesn't already exist.
	 *
	 * @uses WP_Object_Cache::get Checks to see if the cache already has data.
	 * @uses WP_Object_Cache::set Sets the data after the checking the cache
	 *		contents existence.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $key What to call the contents in the cache
	 * @param mixed $data The contents to store in the cache
	 * @param string $group Where to group the cache contents
	 * @param int $expire When to expire the cache contents
	 * @return bool False if cache key and group already exist, true on success
	 */
	function add( $key, $data, $group = 'default', $expire = '' ) {
		$rc = false;
		
		if ( function_exists('wp_suspend_cache_addition') && wp_suspend_cache_addition() )
			return false;

		if ( empty ($group) )
			$group = 'default';
		$ky = $this->get_key($group,$key);
		
		if (is_object($this->memcache_pool)) {
			if (false !== $this->memcache_pool->get($ky))
				$rc = $this->memcache_pool->delete($ky);
			
			$rc = $this->memcache_pool->add($ky,$data,0,$expire);
		}
		
		if (is_object($this->fc)) {
			$rc = $this->fc->put($ky,$data,$expire);			
		}
		
		if ($rc == false) {
			if (false !== $this->get($key, $group))
				return false;
	
			return $this->set($key, $data, $group, $expire);
		}

		return $rc;
	}

	/**
	 * Sets the list of global groups.
	 *
	 * @since 3.0.0
	 *
	 * @param array $groups List of groups that are global.
	 */
	function add_global_groups( $groups ) {
		$groups = (array) $groups;

		$this->global_groups = array_merge($this->global_groups, $groups);
		$this->global_groups = array_unique($this->global_groups);
	}

	/**
	 * Decrement numeric cache item's value
	 *
	 * @since 3.3.0
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset The amount by which to decrement the item's value.  Default is 1.
	 * @param string $group The group the key is in.
	 * @return false|int False on failure, the item's new value on success.
	 */
	function decr( $key, $offset = 1, $group = 'default' ) {
		if (is_object($this->memcache_pool)) {
			$ky = $this->get_key($group,$key);
			$rc = $this->memcache_pool->decrement($ky,$offset);
			if ($rc == true) {
				return $this->memcache_pool->get($ky);
			}
		}

		// TODO: add decrement to file cache 
		
		if ( ! isset( $this->cache[ $group ][ $key ] ) )
			return false;

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) )
			$this->cache[ $group ][ $key ] = 0;

		$offset = (int) $offset;

		$this->cache[ $group ][ $key ] -= $offset;

		if ( $this->cache[ $group ][ $key ] < 0 )
			$this->cache[ $group ][ $key ] = 0;

		return $this->cache[ $group ][ $key ];

	}

	/**
	 * Remove the contents of the cache key in the group
	 *
	 * If the cache key does not exist in the group and $force parameter is set
	 * to false, then nothing will happen. The $force parameter is set to false
	 * by default.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $key What the contents in the cache are called
	 * @param string $group Where the cache contents are grouped
	 * @param bool $force Optional. Whether to force the unsetting of the cache
	 *		key in the group
	 * @return bool False if the contents weren't deleted and true on success
	 */
	function delete($key, $group = 'default', $force = false) {
		if (empty ($group))
			$group = 'default';

		if (!$force && false === $this->get($key, $group))
			return false;

		$ky = $this->get_key($group,$key);
		
		if (is_object($this->memcache_pool)) {
			$rc = $this->memcache_pool->delete($ky);
		}

		if (is_object($this->fc)) {
			$this->fc->clear($ky);
		}		

		unset ($this->cache[$group][$key]);
		return true;
	}

	/**
	 * Clears the object cache of all data
	 *
	 * @since 2.0.0
	 *
	 * @return bool Always returns true
	 */
	function flush() {
		if (is_object($this->memcache_pool)) $this->memcache_pool->flush();
		
		if (is_object($this->fc)) $this->fc->flush();
		
		$this->cache = array ();

		return true;
	}

	/**
	 * Retrieves the cache contents, if it exists
	 *
	 * The contents will be first attempted to be retrieved by searching by the
	 * key in the cache group. If the cache is hit (success) then the contents
	 * are returned.
	 *
	 * On failure, the number of cache misses will be incremented.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $key What the contents in the cache are called
	 * @param string $group Where the cache contents are grouped
	 * @param string $force Whether to force a refetch rather than relying on the local cache (default is false)
	 * @return bool|mixed False on failure to retrieve contents or the cache
	 *		contents on success
	 */
	function get( $key, $group = 'default', $force = false) {
		$rc = false;
		
		if ( empty ($group) )
			$group = 'default';
			
		$ky = $this->get_key($group,$key);
		if (is_object($this->memcache_pool)) {
			$data = $this->memcache_pool->get($ky);
			if ($data !== false) {
				$stats = $this->memcache_pool->getStats();
				$this->cache_hits = $stats['get_hits'];
				$this->cache_misses = $stats['get_misses'];
				$this->cache_source = "FROM_MEMCACHE";
				return $data;
			} else {
				$this->cache_misses += 1;				
			}
		} else if(is_object($this->fc)) {
			$data = $this->fc->get($ky);
			if ($data !== false)  {
				$this->cache_source = "FROM_FILE_CACHE";
				return $data;
			} else {
				$this->cache_misses += 1;				
			}
		} else {
			if ( isset ($this->cache[$group][$key]) ) {
				$this->cache_hits += 1;
				if ( is_object($this->cache[$group][$key]) )
					return clone $this->cache[$group][$key];
				else
					return $this->cache[$group][$key];
			} else {
				$this->cache_misses += 1;				
			}
		}
	
		return $rc;
	}

	/**
	 * Increment numeric cache item's value
	 *
	 * @since 3.3.0
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset The amount by which to increment the item's value.  Default is 1.
	 * @param string $group The group the key is in.
	 * @return false|int False on failure, the item's new value on success.
	 */
	function incr( $key, $offset = 1, $group = 'default' ) {
		if (is_object($this->memcache_pool)) {
			$ky = $this->get_key($group,$key);
			$rc = $this->memcache_pool->increment($ky,$offset);
			if ($rc == true) {
				return $this->memcache_pool->get($ky);
			}
		}
		
		// TODO: add increment to file cache 
		
		if ( ! isset( $this->cache[ $group ][ $key ] ) )
			return false;

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) )
			$this->cache[ $group ][ $key ] = 0;

		$offset = (int) $offset;

		$this->cache[ $group ][ $key ] += $offset;

		if ( $this->cache[ $group ][ $key ] < 0 )
			$this->cache[ $group ][ $key ] = 0;

		return $this->cache[ $group ][ $key ];
		
	}

	/**
	 * Replace the contents in the cache, if contents already exist
	 *
	 * @since 2.0.0
	 * @see WP_Object_Cache::set()
	 *
	 * @param int|string $key What to call the contents in the cache
	 * @param mixed $data The contents to store in the cache
	 * @param string $group Where to group the cache contents
	 * @param int $expire When to expire the cache contents
	 * @return bool False if not exists, true if contents were replaced
	 */
	function replace($key, $data, $group = 'default', $expire = '') {
		if (empty ($group))
			$group = 'default';

		if ( false === $this->get($key, $group) )
			return false;

		return $this->set($key, $data, $group, $expire);
	}

	/**
	 * Reset keys
	 *
	 * @since 3.0.0
	 */
	function reset() {
		// Clear out non-global caches since the blog ID has changed.
		foreach ( array_keys($this->cache) as $group ) {
			if ( !in_array($group, $this->global_groups) ) {
				unset($this->cache[$group]);
			} 
		}
	}

	/**
	 * Sets the data contents into the cache
	 *
	 * The cache contents is grouped by the $group parameter followed by the
	 * $key. This allows for duplicate ids in unique groups. Therefore, naming of
	 * the group should be used with care and should follow normal function
	 * naming guidelines outside of core WordPress usage.
	 *
	 * The $expire parameter is not used, because the cache will automatically
	 * expire for each time a page is accessed and PHP finishes. The method is
	 * more for cache plugins which use files.
	 *
	 * @since 2.0.0
	 *
	 * @param int|string $key What to call the contents in the cache
	 * @param mixed $data The contents to store in the cache
	 * @param string $group Where to group the cache contents
	 * @param int $expire Not Used
	 * @return bool Always returns true
	 */
	function set($key, $data, $group = 'default', $expire = '') {
		$rc = true;
		
		if ( empty ($group) )
			$group = 'default';

		if ( NULL === $data )
			$data = '';

		if ( is_object($data) )
			$data = clone $data;

		$ky = $this->get_key($key,$group);
		
		if (is_object($this->memcache_pool)) {
			if (false !== $this->memcache_pool->get($ky)) {
				$rc = $this->memcache_pool->replace($ky,$data,0,$expire);
			} else {
				$rc = $this->memcache_pool->add($ky,$data,0,$expire);				
			}
		}
		
		if (is_object($this->fc)) {
			$rc = $this->fc->put($ky,$data,$expire);			
		}
		
		$this->cache[$group][$key] = $data;
		
		return $rc;
	}

	/**
	 * Echoes the stats of the caching.
	 *
	 * Gives the cache hits, and cache misses. Also prints every cached group,
	 * key and the data.
	 *
	 * @since 2.0.0
	 */
	function stats() {
		if (is_object($this->memcache_pool)) {
			echo '<ul>';
			foreach ($this->memcache_pool->getStats() as $group => $cache) {
				echo "<li><strong>Group:</strong> $group - ( " . number_format( strlen( serialize( $cache ) ) / 1024, 2 ) . 'k )</li>';
			}
			echo '</ul>';
		} else {
			echo "<p>";
			echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
			echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
			echo "</p>";
			echo '<ul>';
			foreach ($this->cache as $group => $cache) {
				echo "<li><strong>Group:</strong> $group - ( " . number_format( strlen( serialize( $cache ) ) / 1024, 2 ) . 'k )</li>';
			}
			echo '</ul>';
		}
	}

	function failure_callback($host, $port) {
		//error_log("Connection failure for $host:$port\n", 3, '/tmp/memcached.txt');
		if (defined("WP_TRUECACHE_TRACEON")) header("WPTRUECACHE-HA TRACE [".microtime()."] Connection failure for $host:$port");
	}

	function get_key($key, $group) {	
		if ( empty($group) )
			$group = 'default';

		if ( false !== array_search($group, $this->global_groups) )
			$prefix = $this->global_prefix;
		else
			$prefix = $this->blog_prefix;

		return preg_replace('/\s+/', '', "$prefix$group:$key");
	}
	
	function close() {
		if (is_object($this->memcache_pool)) {
			$this->memcache_pool->close();
		}		
	}

	/**
	 * Sets up object properties; PHP 5 style constructor
	 *
	 * @since 2.0.8
	 * @return null|WP_Object_Cache If cache is disabled, returns null.
	 */
	function __construct() {
		/**
		 * @todo This should be moved to the PHP4 style constructor, PHP5
		 * already calls __destruct()
		 */
		register_shutdown_function(array(&$this, "__destruct"));
		
		$this->mc = get_memcache_server_list();
		$this->memcache_connections = count($this->mc);
		
		if ($this->memcache_connections > 0) {
			$this->memcache_pool = new Memcache();
			foreach ($this->mc as $mcserver) {
				$rc = $this->memcache_pool->addServer($mcserver['host'], $mcserver['port'], true, 1, 1, 15, true, array($this, 'failure_callback'));
			}			
		}
		
		$this->fc = new FileCache();
	}

	/**
	 * Will save the object cache before object is completely destroyed.
	 *
	 * Called upon object destruction, which should be when PHP ends.
	 *
	 * @since  2.0.8
	 *
	 * @return bool True value. Won't be used by PHP
	 */
	function __destruct() {
		return true;
	}
}

global $wptruecache_object_cache;



?>
