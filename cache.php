<?php
/**
 *	Filename: cache.php
 *
 *	Description: custom boot loader that will check the cache during the boot process prior to any database object being created.
 *
 */

global $wptruecache_object_cache;

if (file_exists(dirname(__FILE__).'/drop-ins/object-cache.php')) {
	include(dirname(__FILE__).'/drop-ins/object-cache.php');
}

function wptruecache_check_cache() {

	// Check if caching is enable, return immediately if not!
	if (defined('WP_CACHE') && WP_CACHE == FALSE) {
		return;
	}
	
	if (!defined('WP_CACHE')) {
		return;
	}

	wptruecache_cache_init();

	if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$protocol = 'https://';
	} else {
		$protocol = 'http://';
	}
	$url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'];
	
	//$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'];
	$url = rtrim($url, '/');
	//preg_match('/png|gif|jpg|js|feed|wp-login|wp-admin|comment|s=/i', $url, $result);
	$nocache_items = WPTRUECACHE_NOCACHE_ITEMS;
	if (defined('WP_TRUECACHE_NOCACHE_ITEMS')) $nocache_items = WP_TRUECACHE_NOCACHE_ITEMS;
	preg_match('/'.$nocache_items.'|s=/i', $url, $result);
	if (isset($_COOKIE)) {
		foreach ($_COOKIE as $key => $val) {
		   $cookies[] = $key.'='.$val;
		}
	}
	if (!empty($cookies)) {
	  $cook = implode(",",$cookies);
	  preg_match('/comment|admin|logged/i',$cook,$cokres);
	} else {
	  $cokres = "";
	}
	if (empty($result) && empty($cokres)) {
		if (defined('WPTRUECACHE_COMMENTLOCK') && WPTRUECACHE_COMMENTLOCK == TRUE) {
			$admin_lock = wptruecache_cache_get(md5($_SERVER['HTTP_HOST'].'COMMENT_UPDATE_LOCK'));
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Admin comment update lock ".($admin_lock ? "FOUND" : "NOT FOUND"));
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Admin comment update lock ".($admin_lock ? "FOUND" : "NOT FOUND"));
			if ($admin_lock !== FALSE) {
				$sleep_timer = WPTRUECACHE_WAIT;
				if (defined('WP_TRUECACHE_WAIT')) $sleep_timer = WP_TRUECACHE_WAIT;
				sleep($sleep_timer);	// wait for a fixed time
				header('Location: '.$url);		// then try loading the page again?
				exit;
			}			
		}
		
		if (defined('WPTRUECACHE_PAGELOCK') && WPTRUECACHE_PAGELOCK == TRUE) {
			// check if key is locked?
			$locked = wptruecache_cache_get(md5('lock:'.$url));
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Page lock ".($locked ? "FOUND" : "NOT FOUND")." for ".$url);
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Page lock ".($locked ? "FOUND" : "NOT FOUND")." for ".$url);
			if ($locked !== FALSE) {
				// key is locked
				$sleep_timer = WPTRUECACHE_WAIT;
				if (defined('WP_TRUECACHE_WAIT')) $sleep_timer = WP_TRUECACHE_WAIT;
				sleep($sleep_timer);	// wait for a fixed time
				header('Location: '.$url);		// then try loading the page again?
				exit;
			}			
		}

		
		$temp = wptruecache_cache_get(md5($url)); 
		if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Page contents ".($temp ? "FOUND" : "NOT FOUND")." in memcache for ".$url);
		if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Page contents ".($temp ? "FOUND" : "NOT FOUND")." in memcache for ".$url);
		if ($temp !== FALSE) {
			header('Pragma: public');  // if change this to private, the user will only see their posts.
			header('Vary: Accept-Encoding');
			header('Cache-Control: max-age=30');
			if (function_exists("wptruecache_cache_get_source")) {
				$cache_source = wptruecache_cache_get_source();
			}
			header('Cache-Plugin: '.$cache_source);
			header('Cache-Plugin-Version: '.WPTRUECACHE_VERSION);

			$http_accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
			$encoding = false;
			if (defined('WP_TRUECACHE_COMPRESSION') && (WP_TRUECACHE_COMPRESSION == true))
			{
				// don't compress when PHP is already doing it
				if ( !(1 == ini_get( 'zlib.output_compression' ) || "on" == strtolower( ini_get( 'zlib.output_compression' ) )) ) {
					if( strpos($http_accept_encoding, 'x-gzip') !== false ){
	        			$encoding = 'x-gzip';
	    			} elseif( strpos($http_accept_encoding,'gzip') !== false ){
	        			$encoding = 'gzip';
	    			}
				} 
			}

			$pid = wptruecache_cache_get(md5($url.'_POSTINFO'));
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Post info ".($pid ? "FOUND" : "NOT FOUND")." in memcache for ".$url);
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Post info ".($pid ? "FOUND" : "NOT FOUND")." in memcache for ".$url);
			
			//if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Server pool ".($rc ? "CLOSED" : "NOT CLOSED"));
			$now = time();
			$start = date('r',$now);
			$expires = $now + 30;
			$end = gmdate("D, d M Y H:i:s", $expires);
			header('Date: '.$start);
			header('Expires: '.$end.' GMT');
			header('Content-Length: '.strlen($temp));
			if ($pid !== FALSE) {
				$postinfo = unserialize($pid);
				header('Last-Modified: ' . gmdate("D, d M Y H:i:s",strtotime($postinfo['post_modified'])) . ' GMT', true);
			}
			

			if (!empty($temp)) {
			    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
					$_SERVER['HTTP_IF_MODIFIED_SINCE'] &&
					strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= strtotime($postinfo['post_modified'])) {
				    	header('HTTP/1.0 304 Not Modified');
				    	header('ETag: WPTRUECACHE-304');
			    }

				header('WPTRUECACHE-User: Visitor');
				header("WPTRUECACHE-HA Memcache Connections: ".$memcache_connections);
				if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: END of wp_cache_postload");
				if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: END of wp_cache_postload");
								
				if ($encoding) {
					ob_start("ob_gzhandler");
					eval( '?>' . $temp . '<?php ' ); 
					echo "\n<!-- Compression = gzip -->\n";
					ob_end_flush();						
				} else {
					ob_end_clean();
	   		    	echo $temp;						
				}
				if (defined('WP_TRUECACHE_HEADERSTATS')) {
					header('WP-TrueCache-Lock-Timeout: '.(defined('WP_TRUECACHE_LOCK_TIMEOUT') ? WP_TRUECACHE_LOCK_TIMEOUT : LOCK_TIMEOUT));
					header('WP-TrueCache-Memcache-Timeout: '.(defined('WP_TRUECACHE_MEMCACHE_TIMEOUT') ? WP_TRUECACHE_MEMCACHE_TIMEOUT : MEMCACHE_TIMEOUT));
					header('WP-TrueCache-Wait: '.(defined('WP_TRUECACHE_WAIT') ? WP_TRUECACHE_WAIT : WPTRUECACHE_WAIT ));
					header('WP-TrueCache-Memcache-Server-Pool: '.(defined('WP_MEMCACHE_FULL_SERVER_LIST') ? WP_MEMCACHE_FULL_SERVER_LIST : (defined('WP_MEMCACHE_SERVERS') ? WP_MEMCACHE_SERVERS :'Not defined')));
				}

	   		    exit;
			}
		} else {
			// no cached item, then must pull from database
			// we need to lock this key first,
			$locktimeout = LOCK_TIMEOUT;
			if (defined('WP_TRUECACHE_LOCK_TIMEOUT')) $locktimeout = WP_TRUECACHE_LOCK_TIMEOUT;

			//$lock = wptruecache_cache_add(md5('lock:' . $url), 1, false, $locktimeout);
			$lock = admin_memcache_control('ADD',md5('lock:' . $url), 1, false, $locktimeout);
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i++."]: Page lock ".($lock ? "ADDED" : "NOT ADDED")." for ".$url);
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i++."]: Page lock ".($lock ? "ADDED" : "NOT ADDED")." for ".$url);
		}
	}
}
?>