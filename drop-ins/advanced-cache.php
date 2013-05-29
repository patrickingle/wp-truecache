<?php
/*
Plugin Name: WP-TrueCache Page Cache
Description: Memcache page caching
Version: 1.0.19
Plugin URI: http://github.com/patrickingle/wp-truecache
Author: Patrick Ingle

Install this file to wp-content/advanced-cache.php
*/


define( 'WPTRUECACHE_ADVANCED_CACHE_INSTALLED', 'YES' );


global $Log;


function gzip_accepted(){
	if ( 1 == ini_get( 'zlib.output_compression' ) || "on" == strtolower( ini_get( 'zlib.output_compression' ) ) ) // don't compress WP-Cache data files when PHP is already doing it
		return false;

	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) return false;
	return 'gzip';
}

class wptruecache_cache {
	var $cookies = array();
	var $author = array();
	var $http_cookies;
	var $mcservers = array();
	var $mcobjs = array();
	var $nocache = false;

	function __construct() {

		$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'];
		$url = rtrim($url,'/');
		//preg_match('/wp-login|wp-admin|comment|s=/i', $url, $result);
		$nocache_items = WP_TRUECACHE_NOCACHE_ITEMS;
		if (defined('WP_TRUECACHE_NOCACHE_ITEMS')) $nocache_items = WPTRUECACHE_NOCACHE_ITEMS;
		preg_match('/'.$nocache_items.'|s=/i', $url, $result);
		foreach ($_COOKIE as $key => $val) {
		   $cookies[] = $key.'='.$val;
		}
		$cokres="";
		if (!empty($cookies)) {
		  $cook = implode(",",$cookies);
		  preg_match('/comment/i',$cook,$cokres);
		} else {
		  $cokres = "";
		}
		if (empty($result) && empty($cokres)) {
			unset($_COOKIE);
		} else {
			$this->nocache = true;
		}
	}

	function __destruct() {
	}

	function ob(&$output) {
		global $Log;
		
		if (current_user_can('administrator') == false &&
			current_user_can('editor') == false &&
			current_user_can('author') == false &&
			current_user_can('contributor') == false &&
			current_user_can('subscriber') == false &&
			$this->nocache == false) {
			
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: START of output buffering");
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: START of output buffering");

			// Do not cache on posts, such as visitors posting comments
        	if ($_SERVER['REQUEST_METHOD'] == 'POST') return $output;

			if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
				$protocol = 'https://';
			} else {
				$protocol = 'http://';
			}
			
			$server = $_SERVER;
			$url = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'];
			//$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'];
			$url = rtrim($url, '/');
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Buffering page contents for ".$url);
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: Buffering page contents for ".$url);
			
			//preg_match('/png|gif|jpg|js|feed|wp-login|wp-admin|comment|s=/i', $url, $result);
			$nocache_items = WP_TRUECACHE_NOCACHE_ITEMS;
			if (defined('WP_TRUECACHE_NOCACHE_ITEMS')) $nocache_items = WP_TRUECACHE_NOCACHE_ITEMS;
			preg_match('/'.$nocache_items.'|s=/i', $url, $result);
			if (empty($result) && !isset($_COOKIE)) {
				if ($url !== FALSE) {
					if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]:");
					$temp = wptruecache_cache_get(md5($url));
					if ($temp === FALSE) {
						$id = url_to_postid($url);
						$postinfo = get_post($id,ARRAY_A);
	
						$memcache_timeout = MEMCACHE_TIMEOUT;
						if (defined('WP_TRUECACHE_MEMCACHE_TIMEOUT')) $memcache_timeout = WP_TRUECACHE_MEMCACHE_TIMEOUT;
						$total_memcache_timeout = time()+$memcache_timeout;
						$rc = wptruecache_cache_add(md5($url),$output,0,$total_memcache_timeout);
						if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: New page contents ".($rc ? "ADDED" : "NOT ADDED")." for ".$url." with memcache total timeout of ".$total_memcache_timeout);
						if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: New page contents ".($rc ? "ADDED" : "NOT ADDED")." for ".$url." with memcache total timeout of ".$total_memcache_timeout);
						
						if ($rc == true) {
							$rc = wptruecache_cache_delete(md5($url.'_POSTINFO'));
							if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Post info ".($rc ? "DELETD" : "NOT DELETED (DID NOT EXIST)")." for ".$url);
							if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Post info ".($rc ? "DELETD" : "NOT DELETED (DID NOT EXIST)")." for ".$url);
							
							$rc = wptruecache_cache_add(md5($url.'_POSTINFO'),serialize($postinfo),0,time()+$memcache_timeout);
							if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Post info ".($rc ? "ADDED" : "NOT ADDED")." for ".$url);
							if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Post info ".($rc ? "ADDED" : "NOT ADDED")." for ".$url);
						}
					}
				
					if (function_exists("wp_cache_connection_count")) {
						$memcache_connections = wp_cache_connection_count();
						header("WP-TrueCache Memcache Connections: ".$memcache_connections);				
					}
				
					// remove any lock?
					//$rc = wp_cache_delete(md5('lock:'.$url));
					$rc = admin_memcache_control('DEL','lock:'.$url);
					if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Page lock ".($rc ? "REMOVED" : "NOT REMOVED")." for ".$url);
					if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Page lock ".($rc ? "REMOVED" : "NOT REMOVED")." for ".$url);
				}
			}
		}

		if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: END of output buffering");
		if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: END of output buffering");
		return $output;
	}
}

global $wptruecache_cache;


// This function is called very early in the load of the Wordpress Codex!
// Proper design usage can illeviate complete database traffic

function wp_cache_postload() {
	// Check if caching is enable, return immediately if not!
	if (defined('WP_CACHE') && WP_CACHE == FALSE) {
		return;
	}
	
	if (!defined('WP_CACHE')) {
		return;
	}
	
	if (defined('WP_TRUECACHE_DEBUG')) {
		$dbglog = sys_get_temp_dir(). "wptruecache.log";
		header("WP-TrueCache DBG Log: ".$dbglog);
		if (class_exists("Logging",true)) {
			$Log = new Logging($dbglog);
		}
	}
	
	register_shutdown_function('wptruecache_ha_shutdown');

	// first, we just want to stop all output buffering and clean the output buffer, if any
 	ob_end_clean();
	
	if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: START of wp_cache_postload");
	if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: START of wp_cache_postload");

	// Add action filters
	add_action('publish_post','cache_post_page_updated');
//	add_action('edit_post','cache_post_page_updated');
//	add_action('delete_post','cache_post_page_updated');
	add_action('publish_page','cache_post_page_updated');
//	add_action('comment_post','cache_comment_updated');
//	add_action('edit_comment','cache_comment_updated');
//	add_action('delete_comment','cache_comment_updated');
//	add_action('trash_comment','cache_comment_updated');

	add_action('comment_unapproved_to_approved','cache_comment_approved');
	add_action('comment_approved_to_unapproved','cache_comment_approved');
	add_action('comment_approved_to_hold','cache_comment_approved');
	add_action('comment_approved_to_spam','cache_comment_approved');
	add_action('comment_approved_to_trash','cache_comment_approved');

	// Change/reduce the comment cookie lifetime from 347 days to 24 hours
	add_filter('comment_cookie_lifetime','cache_comment_cookie_lifetime');

	header('Cache-Plugin-Version: '.WP_TRUECACHE_VERSION);
	

	$user_type = "Visitor"; // Visitor|Comment Author|Blogger/Admin 

	if (current_user_can('administrator') == true) $user_type = "Administrator";
	elseif (current_user_can('editor') == true) $user_type = "Editor";
	elseif (current_user_can('author') == true) $user_type = "Author";
	elseif (current_user_can('contributor') == true) $user_type = "Contributor";
	elseif (current_user_can('subscriber') == true) $user_type = "Subscriber";
	else {
		if (isset($_COOKIE)) {
			foreach ($_COOKIE as $key => $val) {
				$cookies[] = $key.'='.$val;
			}
		}
			
		if (!empty($cookies)) {
			  $cook = implode(",",$cookies);
			  preg_match('/comment/i',$cook,$comment_author);
			  
			  if (!empty($comment_author)) {
			  	$user_type = "Comment Author";
			  } else {
			  	$user_type = "Visitor";
			  }
		} else {
			$user_type = "Visitor";
		}
	}
	
	header('WP-TrueCache-User: '.$user_type);

	// Enable output buffering for Visitor sessions ONLY!	
	if ($user_type == "Visitor") {
		$wptruecache_cache = new wptruecache_cache();
		ob_start(array(&$wptruecache_cache, 'ob'));
	}

	header('Cache-Plugin: WPTRUECACHE-FROM_DB');	
	if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: END of wp_cache_postload");
	if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: END of wp_cache_postload");
}

function admin_memcache_control($cmd,$key,$data=0,$flag=0,$expiration=0) {
	$result = FALSE;
	$mcservers = get_memcache_server_list();
	
	if (is_array($mcservers)) {
		$default_mc = $mcservers[0]['host'];
		$default_mc_port = $mcservers[0]['port'];
		
		$mcadmin = new Memcache();
		$rc = $mcadmin->connect($default_mc,$default_mc_port);
		if ($rc) {
			switch($cmd) {
				case 'ADD':
					$result = $mcadmin->add($key,$data,$flag,$expiration);
					break;
				case 'GET':
					$result = $mcadmin->get($key);
					break;
				case 'DEL':
					$result = $mcadmin->delete($key);
					break;
			}
		}
		$mcadmin->close();
	}
	return $result;
}


function cache_post_page_updated($id) {
	if (defined('WP_TRUECACHE_CACHEFLUSHALL')) {
		// We're flushing the cache as in Super-Cache and Total Cached
		if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: Flushing memcached");
		if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: Flushing memcached");
		wptruecache_cache_flush();		
	} else {
		$url = get_permalink($id);
		if ($url) {
			$url = rtrim($url, '/');
			$rc = wp_cache_delete(md5($url));
			flush_cached_pages_posts();			
		}
		// Now flush just the home url
		$home_url = get_home_url();
		$home_url = rtrim($home_url,'/');
		if ($home_url) {
			$rc = wptruecache_cache_delete(md5($home_url),0);
			if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: memcache item for url ".$home_url." ".($rc ? "DELETED" : "NOT DELETED"));
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: memcache item for url ".$home_url." ".($rc ? "DELETED" : "NOT DELETED"));
		}
	}
	
	wptruecache_cache_close();
}


function cache_comment_approved($comment) {
	if (defined('WP_TRUECACHE_CACHEFLUSHALL')) {
		// We're flushing the cache when a comment is approved, same as Super Cache 
		wptruecache_cache_flush();
	} else {
		$url = get_permalink($comment->comment_post_ID);
		if ($url) {
			$url = rtrim($url, '/');
			$rc = wptruecache_cache_delete(md5($url));
			if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: memcache item for url ".$url."/ ".($rc ? "DELETED" : "NOT DELETED"));
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: memcache item for url ".$url."/ ".($rc ? "DELETED" : "NOT DELETED"));
			flush_cached_pages_posts();			
		}		
	}

	$rc = wptruecache_cache_close();
	if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: memcache connection is ".($rc ? "CLOSED" : "NOT CLOSED"));
	if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: memcache connection is ".($rc ? "CLOSED" : "NOT CLOSED"));
}

function flush_cached_pages_posts() {
	// first set a global comment update lock
	$memcache_timeout = MEMCACHE_TIMEOUT;
	if (defined('WP_TRUECACHE_MEMCACHE_TIMEOUT')) $memcache_timeout = WP_TRUECACHE_MEMCACHE_TIMEOUT;
	//$rc = wptruecache_cache_add(md5($_SERVER['HTTP_HOST'].'COMMENT_UPDATE_LOCK'),1,0,time()+$memcache_timeout);
	$rc = admin_memcache_control('ADD',md5($_SERVER['HTTP_HOST'].'COMMENT_UPDATE_LOCK'),1,0,time()+$memcache_timeout);
	if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: Admin comment update lock ".($rc ? "HAS BEEN ADDED" : "WAS NOT ADDED"));
	if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."]: Admin comment update lock ".($rc ? "HAS BEEN ADDED" : "WAS NOT ADDED"));
	
	// now go through wp_list_pages, and delete each cached item with their PAGEINFO and LOCK
	$pages = get_pages();
	foreach($pages as $page) {
		$permalink = get_permalink($page->ID);
		$permalink = rtrim($permalink,'/');
		$rc = wptruecache_cache_get(md5($permalink));
		if (defined('WP_TRUECACHE_TRACEON')) header("WP-TrueCache TRACE [".microtime().", ".__LINE__."-".$i."]: memcache page item for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
		if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WP-TrueCache TRACE [".microtime().", ".__LINE__."-".$i."]: memcache page item for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
		$i=0;
		if ($rc !== FALSE) {
			$rc = wptruecache_cache_delete(md5($permalink.'_POSTINFO'));
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache page item for url ".$permalink." postinfo ".($rc ? "DELETED" : "NOT DELETED"));
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache page item for url ".$permalink." postinfo ".($rc ? "DELETED" : "NOT DELETED"));
			$rc = wptruecache_cache_delete(md5($_SERVER['HTTP_HOST'].'lock:'.$permalink));
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache page lock for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache page lock for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
			$i++;
		}
	}
	
	$posts = get_posts();
	foreach($posts as $post) {
		$permalink = get_permalink($post->ID);
		$permalink = rtrim($permalink,'/');
		$rc = wptruecache_cache_get(md5($permalink));
		if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache post item for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
		if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache post item for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
		$i=0;
		if ($rc !== FALSE) {
			$rc = wptruecache_cache_delete(md5($permalink.'_POSTINFO'));
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache post item for url ".$permalink." postinfo ".($rc ? "DELETED" : "NOT DELETED"));
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache post item for url ".$permalink." postinfo ".($rc ? "DELETED" : "NOT DELETED"));
			$rc = wptruecache_cache_delete(md5($_SERVER['HTTP_HOST'].'lock:'.$permalink));
			if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache post lock for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
			if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: memcache post lock for url ".$permalink.($rc ? "DELETED" : "NOT DELETED"));
			$i++;
		}
	}
	
	// then clear the global comment lock
	//$rc = wptruecache_cache_delete(md5($_SERVER['HTTP_HOST'].'COMMENT_UPDATE_LOCK'));
	$rc = admin_memcache_control('DEL',md5($_SERVER['HTTP_HOST'].'COMMENT_UPDATE_LOCK'));
	if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: admin comment update lock ".$permalink.($rc ? "REMOVED" : "NOT REMOVED"));
	if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."]: admin comment update lock ".$permalink.($rc ? "REMOVED" : "NOT REMOVED"));
}


function wptruecache_ha_shutdown() {
	$rc = wptruecache_cache_close();
//	if (defined('WP_TRUECACHE_TRACEON')) header("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Memcache connection ".($rc ? "CLOSED" : "NOT CLOSED")." for ".$url);
	if (defined('WP_TRUECACHE_DEBUG')) $Log->Notice("WPTRUECACHE-HA TRACE [".microtime().", ".__LINE__."-".$i."]: Memcache connection ".($rc ? "CLOSED" : "NOT CLOSED")." for ".$url);
	ob_end_flush();	// stop and clean th output buffering
	do_action('shutdown');
}

function cache_comment_cookie_lifetime($lifetime) {
	//return (60 * 60 * 24); // Set cookie time to expire after 24 hours, default was 347 days!
	$comment_cookie_timeout = COMMENT_COOKIE_TIMEOUT;
	if (defined('WP_TRUECACHE_COMMENT_COOKIE_TIMEOUT')) $comment_cookie_timeout = WP_TRUECACHE_COMMENT_COOKIE_TIMEOUT;
	return ($comment_cookie_timeout);
}

?>
