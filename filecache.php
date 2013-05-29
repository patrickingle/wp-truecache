<?php
/**
 * Caches manipulated variables into files for future use
 * @author		Charles Weiss < c w e i s s [ a t ] f t w m a r k e t i n g . c o m >
 * @copyright	Copyright (C) Fetch The Web 2006-2008
 * @version 0.1
 */

 
class FileCache {
	private $data;
	private $ext = '_FileCache.php';	// The group of the cache file ( appended to end of filename ) 
	private $path = '/tmp/';		// The FULL path to the cached file 
	private $blogcacheid;
	private $blog_cache_dir;
	private $file_prefix = 'wp-cache-';
	private $cache_path;

	function __construct() {
		$this->data = array();

		$request_uri = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', str_replace( '..', '', $_SERVER['REQUEST_URI'] ) );
		if( strpos( $request_uri, '/', 1 ) ) {
			if( $base == '/' ) {
				$blogcacheid = substr( $request_uri, 1, strpos( $request_uri, '/', 1 ) - 1 );
			} else {
				$blogcacheid = str_replace( $base, '', $request_uri );
				$blogcacheid = substr( $blogcacheid, 0, strpos( $blogcacheid, '/', 1 ) );
			}
			if ( '/' == substr($blogcacheid, -1))
				$blogcacheid = substr($blogcacheid, 0, -1);
		}
		$blogcacheid = str_replace( '/', '', $blogcacheid );
		$this->blogcacheid = $blogcacheid;

		if( !defined('WP_CONTENT_DIR') )
			define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

		$this->cache_path = WP_CONTENT_DIR . '/cache/';
		if ( '/' != substr($this->cache_path, -1)) {
			$this->cache_path .= '/';
		}
		
		if( $blogcacheid != '' ) {
			$blog_cache_dir = str_replace( '//', '/', $this->cache_path . "blogs/" . $blogcacheid . '/' );
		} else {
			$blog_cache_dir = $this->cache_path;
		}

		$this->blog_cache_dir = $blog_cache_dir;

		if( false == @is_dir( $this->cache_path )) {
			@mkdir( $this->cache_path );
		}
		
		if( false == @is_dir( $this->blog_cache_dir ) ) {
			@mkdir( $this->cache_path . "blogs" );
			@mkdir( $this->blog_cache_dir );
		}
		
		if( false == @is_dir( $this->blog_cache_dir . 'meta' ) )
			@mkdir( $this->blog_cache_dir . 'meta' );
		
	}
	
	 /**
	 * Fetches variable from the cache if cache exists and data has not expired
	 * @param  $id The id of that variable for use in the filename
	 * @param  $lock Optional parameter instructing the class to lock the file.
	 * @return the cache contents OR false on error
	 */
	 public function get($id) {
		if (isset($this->data[$id])) return $this->data[$id]; // Already set, return to sender
		$key = $this->blogcacheid . md5($id);
		$cache_filename = $this->file_prefix . $key . '.html';
		//$path = realpath($this->blog_cache_dir . $cache_filename);
		//$path = $this->path.base64_encode($id);
		$path = $this->blog_cache_dir . $cache_filename;
		
		if (file_exists($path) && is_readable($path)) { // Check if the cache file exists
			include $path;
			if (isset($expires) && $expires <= time()) {
				$this->clear($id);
				return false;
			} else {
				$cache = file_get_contents($path);
			}
		} else {
			return false;
		}
		
		return $cache;
	}
	
	public function is_expired($path) {
		if (file_exists($path) && is_readable($path)) { // Check if the cache file exists
			try {
				//include $path;
				$meta = file_get_contents($path);
				if (isset($expires) && $expires <= time()) {
					return true;
				}
			} catch(Exception $ex) {
				return false;				
			}
		}
		return false;
	}

	 /**
	 * Sets variable into the cache
	 * @param  $id The id of that variable for use in the filename
	 * @param  $cache The data to be stored
	 * @param  $lifetime The expiration time  (in seconds) from file creation
	 * @return the cache contents OR false on error
	 */
	public function put($id, $cache, $lifetime = 0) {
		$this->data[$id] = $cache;

		if (is_resource($cache)) return "Can't cache resource.";

		$key = $this->blogcacheid . md5($id);
		$cache_filename = $this->file_prefix . $key . '.html';
		//$path = realpath($this->blog_cache_dir . $cache_filename);
		//$path = $this->path.base64_encode($id);
		$path = $this->blog_cache_dir . $cache_filename;
		$fp = @fopen($path, 'w');
		if (!$fp) echo 'Unable to open file for writing.'.$path;
		@flock($fp, LOCK_EX);
		@fwrite($fp, '<?php $cache='.var_export($cache, true).';');
		if ($lifetime > 0) @fwrite($fp, '$expires='.(time()+$lifetime).';');
		@fwrite($fp, ' ?>');
		@flock($fp, LOCK_UN);
		@fclose($fp);
	
		if (file_exists($path)) chmod($path, 666);
		else return false;
		
		return true;
	}

	 /**
	 * Deletes the cache file
	 * @param  $id The id of that variable for use in the filename
	 * @param  $lock Optional parameter instructing the class to lock the file.
	 * @return the true or descriptive string on error
	 */
	public function clear($id) {
		if (isset($this->data[$id])) unset($this->data[$id]);
		$key = $this->blogcacheid . md5($id);
		$cache_filename = $this->file_prefix . $key . '.html';
		//$path = realpath($this->blog_cache_dir . $cache_filename);
		//$pretty_id = base64_encode($id);
		//$path = $this->path.$pretty_id;
		$path = $this->blog_cache_dir . $cache_filename;
		if (file_exists($path) && unlink($path)) return true;
		else return false;
	}
	
	public function flush() {
		$this->rflush($this->cache_path);
		return true;
	}
	
	public function get_blogcachedir() {
		return $this->cache_path;
	}
	
	public function rflush($path) {
		$it = new RecursiveIteratorIterator(
	        new RecursiveDirectoryIterator($path),
	        RecursiveIteratorIterator::CHILD_FIRST
	    );
	    foreach ($it as $file) {
	        if (in_array($file->getBasename(), array('.', '..'))) {
	            continue;
	        } elseif ($file->isDir()) {
	            rmdir($file->getPathname());
	        } elseif ($file->isFile() || $file->isLink()) {
	            unlink($file->getPathname());
	        }
	    }
	    rmdir($path);
	}
}

 /**
	* Example Usage
	include './ObjectCache.class.php';
	$data = array('a','b','c','d','e','f','g');
	$distinct_name = 'lala';
	$cache = new ObjectCache();
	// Cache will last 3600 seconds or 1 hr
	$cache->put($distinct_name, $data, 3600);
	$data2 = $cache->get($distinct_name);
	// Forcibly clear the cache (on data update via admin perhaps
	//$cache->clear($distinct_name);
	print_r($data2);
	unset($cache);

  */
?>
