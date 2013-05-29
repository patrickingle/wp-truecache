<?php

function wpsc_admin_tabs( $current = 0 ) {
	global $wp_db_version;
	if ( $current == 0 ) {
		if ( isset( $_GET[ 'tab' ] ) ) {
			$current = $_GET[ 'tab' ];
		} else {
			$current = 'easy';
		}
	}
	$tabs = array( 'easy' => __( 'Easy', 'filecache' ), 'contents' => __( 'Contents', 'filecache' ) );
	$links = array();
	foreach( $tabs as $tab => $name ) {
		if ( $current == $tab ) {
			$links[] = "<a class='nav-tab nav-tab-active' href='?page=filecache&tab=$tab'>$name</a>";
		} else {
			$links[] = "<a class='nav-tab' href='?page=filecache&tab=$tab'>$name</a>";
		}
	}
	if ( $wp_db_version >= 15477 ) {
		echo '<div id="nav"><h2 class="themes-php">';
		echo implode( "", $links );
		echo '</h2></div>';
	} else {
		echo implode( " | ", $links );
	}
}

$delim = strstr(PHP_OS, "WIN") ? "\\" : "/";

function filecache_filelist($path) {
    global $delim;

   if ($dir = @opendir($path)) {

        while (($element = readdir($dir)) !== false) {

            if (is_dir($path.$delim.$element) && $element != "." && $element != "..") {

                $temp_array[] = $element;

            } elseif ($element != "." && $element != "..") {

                //$array[] = $element;
				//$array[] = @filesize($path.$delim.$element);
				//$array[] = @filesize($path.$delim.$element);
				//$fsize += @filesize($path.$delim.$element);
				$now = time();
				$mtime = filemtime($path.$delim.$element);
				$age = $now - $mtime;
				$meta = unserialize( file_get_contents($path.$delim.$element) );
				$meta[ 'age' ] = $age;

				$cache_max_time = MEMCACHE_TIMEOUT;
				if (defined('WP_TRUECACHE_MEMCACHE_TIMEOUT')) $cache_max_time = WP_TRUECACHE_MEMCACHE_TIMEOUT;

				$fc = new FileCache();
				//if ($fc->is_expired($path.$delim.$element)) {
				//	$expired = TRUE;
				//} else {
				//	$expired = FALSE;
				//}
				if ( $cache_max_time > 0 && $age > $cache_max_time ) {
					$expired = TRUE;
				} else {
					$expired = FALSE;
				}
				
				$array[] = array(	'name' => $element,
									'uri' => $path.$delim.$element,
									'size' => @filesize($path.$delim.$element),
									'age'  => $age,
									'expired' => $expired,
									'max_time' => $cache_max_time);
            }

        }

        if (isset($temp_array)) {

            for ($i = 0; $i < sizeof($temp_array); $i++) {

                $element = $temp_array[$i];

                $array[$element] = filecache_filelist($path.$delim.$element);
				//$fsize += filecache_size($path.$delim.$element);
            }

        }

        closedir($dir);

    }

    return (isset($array) ? $array : false);
}

function filecache_size($filelist,$size=0) {

	foreach ($filelist as $file) {
		if (isset($file['size'])) {
			$size += $file['size'];	
		} else if (is_array($file)) {
			$size += filecache_size($file,$size);
		} 
	}	
	return $size;
}

function filecache_cachecount($filelist,$count=0) {
	foreach ($filelist as $file) {
		if (!is_array($file)) {
			$count++;
		} else if (is_array($file)) {
			$count = filecache_cachecount($file,$count);
		} 
	}	
	return $count;		
}

function filecache_expiredpages($filelist,$count=0) {
	foreach ($filelist as $file) {
		if (isset($file['expired'])) {
			if ($file['expired'] == TRUE) {
				$array[] = $file['name'];
			}
		} else if (is_array($file)) {
			$array[] = filecache_expiredpages($file,$size);
		} 
	}	
	return $array;	
}


function wp_cache_files() {
	global $cache_path, $file_prefix, $cache_max_time, $valid_nonce, $supercachedir, $cache_enabled, $super_cache_enabled, $blog_cache_dir, $cache_compression;
	global $wp_cache_object_cache, $wp_cache_preload_on;

	if ( '/' != substr($cache_path, -1)) {
		$cache_path .= '/';
	}
	
	$nonce = $_REQUEST['_wpnonce'];
	$result = wp_verify_nonce($nonce,'wp-cache');
	if ($result != FALSE) $valid_nonce = TRUE;

	if ( $valid_nonce ) {
		if(isset($_REQUEST['wp_delete_cache'])) {
			wptruecache_wp_cache_clean_cache($file_prefix);
			$_GET[ 'action' ] = 'regenerate_cache_stats';
		}
		if(isset($_REQUEST['wp_delete_expired'])) {
			wptruecache_wp_cache_clean_expired($file_prefix);
			$_GET[ 'action' ] = 'regenerate_cache_stats';
		}
	}
	echo "<a name='listfiles'></a>";
	echo '<fieldset class="options" id="show-this-fieldset"><h3>' . __( 'Cache Contents', 'filecache' ) . '</h3>';

	if ( $wp_cache_object_cache ) {
		echo "<p>" . __( "Object cache in use. No cache listing available.", 'filecache' ) . "</p>";
		wp_cache_delete_buttons();
		echo "</fieldset>";
		return false;
	}

	$cache_stats = get_option( 'filecache_stats' );
	//if ( !is_array( $cache_stats ) || ( $valid_nonce && array_key_exists('action', $_GET) && $_GET[ 'action' ] == 'regenerate_cache_stats' ) ) {
	$fc = new FileCache();
	$blog_cache_dir = $fc->get_blogcachedir();
	
	if ( $_GET[ 'action' ] == 'regenerate_cache_stats' ) {
		$list_files = false; // it doesn't list supercached files, and removing single pages is buggy
		$count = 0;
		$expired = 0;
		$now = time();
 		$filearray = filecache_filelist($blog_cache_dir);
		$fsize = filecache_size($filearray);
		$expired_pages = filecache_expiredpages($filearray);
		$cached_list = $filearray;
		/*
		while( false !== ($file = readdir($handle))) {
			if ( preg_match("/^$file_prefix.*\/", $file) ) {
				//$content_file = preg_replace("/meta$/", "html", $file);
				$mtime = filemtime( $blog_cache_dir . '/' . $file );
				if ( ! ( $fsize = @filesize( $blog_cache_dir . $content_file ) ) ) 
					continue; // .meta does not exists
		
				$age = $now - $mtime;
		*/

				if ( $cache_max_time > 0 && $age > $cache_max_time ) {
					$expired++;
				} else {
					$count++;
				}
				$wp_cache_fsize += $fsize;
				$fsize = intval($fsize/1024);
		/*
			}
		}
		*/
		if( $wp_cache_fsize != 0 ) {
			$wp_cache_fsize = $wp_cache_fsize/1024;
		} else {
			$wp_cache_fsize = 0;
		}
		if( $wp_cache_fsize > 1024 ) {
			$wp_cache_fsize = number_format( $wp_cache_fsize / 1024, 2 ) . "MB";
		} elseif( $wp_cache_fsize != 0 ) {
			$wp_cache_fsize = number_format( $wp_cache_fsize, 2 ) . "KB";
		} else {
			$wp_cache_fsize = '0KB';
		}
	
		// Supercache files
		$now = time();
		$cached_count = filecache_cachecount($filearray);
		$sizes = array( 'expired' => count($expired_pages), 'expired_list' => $expired_pages, 'cached' => $cached_count, 'cached_list' => $cached_list, 'ts' => 0 );
	
		/*
		if (is_dir($supercachedir)) {
			if( $dh = opendir( $supercachedir ) ) {
				while( ( $entry = readdir( $dh ) ) !== false ) {
					if ($entry != '.' && $entry != '..') {
						$sizes = wpsc_dirsize( trailingslashit( $supercachedir ) . $entry, $sizes );
					}
				}
				closedir($dh);
			}
		} else {
			$filem = @filemtime( $supercachedir );
			if ( false == $wp_cache_preload_on && is_file( $supercachedir ) && $cache_max_time > 0 && $filem + $cache_max_time <= $now ) {
				$sizes[ 'expired' ] ++;
				if ( $valid_nonce && $_GET[ 'listfiles' ] )
					$sizes[ 'expired_list' ][ str_replace( $cache_path . 'supercache/' , '', $supercachedir ) ] = $now - $filem;
			} else {
				if ( $valid_nonce && $_GET[ 'listfiles' ] && $filem )
					$sizes[ 'cached_list' ][ str_replace( $cache_path . 'supercache/' , '', $supercachedir ) ] = $now - $filem;
			}
		} 
		*/
		$sizes[ 'ts' ] = time();
		$cache_stats = array( 'generated' => time(), 'filecache' => $sizes, 'wpcache' => array( 'cached' => $count, 'expired' => $expired, 'fsize' => $wp_cache_fsize ) );
		update_option( 'filecache_stats', $cache_stats );
	} // regenerate stats cache

	echo "<p>" . __( 'Cache stats are not automatically generated. You must click the link below to regenerate the stats on this page.', 'filecache' ) . "</p>";
	echo "<a href='" . wp_nonce_url( add_query_arg( array( 'page' => 'filecache', 'tab' => 'contents', 'action' => 'regenerate_cache_stats' ) ), 'wp-cache' ) . "'>" . __( 'Regenerate cache stats', 'filecache' ) . "</a>";
	if ( is_array( $cache_stats ) ) {
		echo "<p>" . sprintf( __( 'Cache stats last generated: %s minutes ago.', 'filecache' ), number_format( ( time() - $cache_stats[ 'generated' ] ) / 60 ) ) . "</p>";
	}
	$cache_stats = get_option( 'filecache_stats' );


	if ( is_array( $cache_stats ) ) {
//		echo "<p><strong>" . __( 'WP-Cache', 'filecache' ) . " ({$cache_stats[ 'wpcache' ][ 'fsize' ]})</strong></p>";
//		echo "<ul><li>" . sprintf( __( '%s Cached Pages', 'filecache' ), $cache_stats[ 'wpcache' ][ 'cached' ] ) . "</li>";
//		echo "<li>" . sprintf( __( '%s Expired Pages', 'filecache' ),    $cache_stats[ 'wpcache' ][ 'expired' ] ) . "</li></ul>";
		$divisor = $cache_compression == 1 ? 2 : 1;
//		if ( array_key_exists('fsize', (array)$cache_stats[ 'filecache' ]) )
//			$fsize = $cache_stats[ 'wpcache' ][ 'fsize' ] / 1024;
//		else
//			$fsize = 0;
//		if( $fsize > 1024 ) {
//			$fsize = number_format( $fsize / 1024, 2 ) . "MB";
//		} elseif( $fsize != 0 ) {
//			$fsize = number_format( $fsize, 2 ) . "KB";
//		} else {
//			$fsize = "0KB";
//		}
		echo "<p><strong>" . __( 'FileCache', 'filecache' ) . " ({$cache_stats[ 'wpcache' ][ 'fsize' ]})</strong></p>";
		echo "<ul><li>" . sprintf( __( '%s Cached Pages', 'filecache' ), intval( $cache_stats[ 'filecache' ][ 'cached' ] / $divisor ) ) . "</li>";
		if (isset($now) && isset($sizes))
			$age = intval(($now - $sizes['ts'])/60);
		else
			$age = 0;
		echo "<li>" . sprintf( __( '%s Expired Pages', 'filecache' ), intval( $cache_stats[ 'filecache' ][ 'expired' ] / $divisor ) ) . "</li></ul>";

		if ( $valid_nonce && array_key_exists('listfiles', $_GET) && $_GET[ 'listfiles' ] ) {
			echo "<div style='padding: 10px; border: 1px solid #333; height: 400px; width: 70%; overflow: auto'>";
			/*
			if ( is_array( $cached_list ) && !empty( $cached_list ) ) {
				echo "<h4>" . __( 'Fresh WP-Cached Files', 'filecache' ) . "</h4>";
				echo "<table class='widefat'><tr><th>#</th><th>" . __( 'URI', 'filecache' ) . "</th><th>" . __( 'Key', 'filecache' ) . "</th><th>" . __( 'Age', 'filecache' ) . "</th><th>" . __( 'Delete', 'filecache' ) . "</th></tr>";
				$c = 1;
				$flip = 1;
				ksort( $cached_list );
				foreach( $cached_list as $age => $d ) {
					foreach( $d as $details ) {
						$bg = $flip ? 'style="background: #EAEAEA;"' : '';
						echo "<tr $bg><td>$c</td><td> <a href='http://{$details[ 'uri' ]}'>" . $details[ 'uri' ] . "</a></td><td> " . str_replace( $details[ 'uri' ], '', $details[ 'key' ] ) . "</td><td> {$age}</td><td><a href='" . wp_nonce_url( add_query_arg( array( 'page' => 'filecache', 'action' => 'deletewpcache', 'uri' => base64_encode( $details[ 'uri' ] ) ) ), 'wp-cache' ) . "#listfiles'>X</a></td></tr>\n";
						$flip = !$flip;
						$c++;
					}
				}
				echo "</table>";
			}
			$expired_list = $cache_stats['filecache']['expired_list'];
			if ( is_array( $expired_list ) && !empty( $expired_list ) ) {
				echo "<h4>" . __( 'Stale WP-Cached Files', 'filecache' ) . "</h4>";
				echo "<table class='widefat'><tr><th>#</th><th>" . __( 'URI', 'filecache' ) . "</th><th>" . __( 'Key', 'filecache' ) . "</th><th>" . __( 'Age', 'filecache' ) . "</th><th>" . __( 'Delete', 'filecache' ) . "</th></tr>";
				$c = 1;
				$flip = 1;
				ksort( $expired_list );
				foreach( $expired_list as $age => $d ) {
					foreach( $d as $details ) {
						$bg = $flip ? 'style="background: #EAEAEA;"' : '';
						echo "<tr $bg><td>$c</td><td> <a href='http://{$details[ 'uri' ]}'>" . $details[ 'uri' ] . "</a></td><td> " . str_replace( $details[ 'uri' ], '', $details[ 'key' ] ) . "</td><td> {$age}</td><td><a href='" . wp_nonce_url( add_query_arg( array( 'page' => 'filecache', 'action' => 'deletewpcache', 'uri' => base64_encode( $details[ 'uri' ] ) ) ), 'wp-cache' ) . "#listfiles'>X</a></td></tr>\n";
						$flip = !$flip;
						$c++;
					}
				}
				echo "</table>";
			}
			*/
			$sizes = $cache_stats['filecache'];
			//echo '<pre>'; print_r($sizes); echo '</pre>';
			if ( is_array( $sizes[ 'cached_list' ] ) & !empty( $sizes[ 'cached_list' ] ) ) {
				echo "<h4>" . __( 'Fresh FileCache Files', 'filecache' ) . "</h4>";
				echo "<table class='widefat'><tr><th>#</th><th>" . __( 'URI', 'filecache' ) . "</th><th>" . __( 'Age', 'filecache' ) . "</th><th>" . __( 'Delete', 'filecache' ) . "</th></tr>";
				$c = 1;
				$flip = 1;
				ksort( $sizes[ 'cached_list' ] );
				foreach( $sizes[ 'cached_list' ] as $age => $d ) {
					if (isset($d['name'])) {
						$bg = $flip ? 'style="background: #EAEAEA;"' : '';
						$fname = basename($d['uri']);
						$uri = WP_CONTENT_URL . '/cache/blogs/' . $fname;
						$age = $d['age'];
						echo "<tr $bg><td>$c</td><td>" . $fname . "</td><td>$age</td><td><a href='" . wp_nonce_url( add_query_arg( array( 'page' => 'filecache', 'action' => 'deletesupercache', 'uri' => base64_encode( $uri ) ) ), 'wp-cache' ) . "#listfiles'>X</a></td></tr>\n";
						$flip = !$flip;
						$c++;						
					}
					//foreach( $d as $uri => $n ) {
					//	$bg = $flip ? 'style="background: #EAEAEA;"' : '';
					//	echo "<tr $bg><td>$c</td><td> <a href='http://{$uri}'>" . $uri . "</a></td><td>$age</td><td><a href='" . wp_nonce_url( add_query_arg( array( 'page' => 'filecache', 'action' => 'deletesupercache', 'uri' => base64_encode( $uri ) ) ), 'wp-cache' ) . "#listfiles'>X</a></td></tr>\n";
					//	$flip = !$flip;
					//	$c++;
					//}
				}
				echo "</table>";
			}
			if ( is_array( $sizes[ 'expired_list' ] ) && !empty( $sizes[ 'expired_list' ] ) ) {
				echo "<h4>" . __( 'Stale FileCache Files', 'filecache' ) . "</h4>";
				echo "<table class='widefat'><tr><th>#</th><th>" . __( 'URI', 'filecache' ) . "</th><th>" . __( 'Age', 'filecache' ) . "</th><th>" . __( 'Delete', 'filecache' ) . "</th></tr>";
				$c = 1;
				$flip = 1;
				ksort( $sizes[ 'expired_list' ] );
				foreach( $sizes[ 'expired_list' ] as $age => $d ) {
					foreach( $d as $uri => $n ) {
						$bg = $flip ? 'style="background: #EAEAEA;"' : '';
						echo "<tr $bg><td>$c</td><td> <a href='http://{$uri}'>" . $uri . "</a></td><td>$age</td><td><a href='" . wp_nonce_url( add_query_arg( array( 'page' => 'filecache', 'action' => 'deletesupercache', 'uri' => base64_encode( $uri ) ) ), 'wp-cache' ) . "#listfiles'>X</a></td></tr>\n";
						$flip = !$flip;
						$c++;
					}
				}
				echo "</table>";
			}
			echo "</div>";
			echo "<p><a href='?page=filecache#top'>" . __( 'Hide file list', 'filecache' ) . "</a></p>";
		} elseif ( $cache_stats[ 'filecache' ][ 'cached' ] > 300 || $cache_stats[ 'filecache' ][ 'expired' ] > 300 || ( $cache_stats[ 'wpcache' ][ 'cached' ] / $divisor ) > 300 || ( $cache_stats[ 'wpcache' ][ 'expired' ] / $divisor) > 300 ) {
			echo "<p><em>" . __( 'Too many cached files, no listing possible.', 'filecache' ) . "</em></p>";
		} else {
			//echo "<p><a href='" . wp_nonce_url( add_query_arg( array( 'page' => 'filecache', 'listfiles' => '1' ) ), 'wp-cache' ) . "#listfiles'>" . __( 'List all cached files', 'filecache' ) . "</a></p>";
		}

		$last_gc = get_option( "filecache_gc_time" );
		if ( $cache_max_time > 0 && $last_gc ) {
			$next_gc = $cache_max_time < 1800 ? $cache_max_time : 600;
			$next_gc_mins = ( time() - $last_gc );
			echo "<p>" . sprintf( __( '<strong>Garbage Collection</strong><br />Last GC was <strong>%s</strong> minutes ago<br />', 'filecache' ), date( 'i:s', $next_gc_mins ) );
			printf( __( "Next GC in <strong>%s</strong> minutes", 'filecache' ), date( 'i:s', $next_gc - $next_gc_mins ) ) . "</p>";
		}
		if ( $cache_max_time > 0 )
			echo "<p>" . sprintf( __( 'Expired files are files older than %s seconds. They are still used by the plugin and are deleted periodically.', 'filecache' ), $cache_max_time ) . "</p>";
	} // cache_stats

	//wp_cache_delete_buttons();
/*
	if ( $_GET[ 'listfiles' ] ) {
		$meta = unserialize( @file_get_contents( $blog_cache_dir . 'meta/' . $file ) );
		if ( $deleteuri != '' && $meta[ 'uri' ] == $deleteuri ) {
			printf( __( "Deleting wp-cache file: <strong>%s</strong><br />", 'filecache' ), $deleteuri );
			@unlink( $blog_cache_dir . 'meta/' . $file );
			@unlink( $blog_cache_dir . $content_file );
			continue;
		}
		$meta[ 'age' ] = $age;
		if ( $cache_max_time > 0 && $age > $cache_max_time ) {
			$expired_list[ $age ][] = $meta;
		} else {
			$cached_list[ $age ][] = $meta;
		}
	}
*/
	echo '</fieldset>';
}

function wp_cache_delete_buttons() {

	echo '<form name="wp_cache_content_expired" action="#listfiles" method="post">';
	echo '<input type="hidden" name="wp_delete_expired" />';
	echo '<div class="submit" style="float:left"><input type="submit" value="Delete Expired" /></div>';
	wp_nonce_field('wp-cache');
	echo "</form>\n";

	echo '<form name="wp_cache_content_delete" action="#listfiles" method="post">';
	echo '<input type="hidden" name="wp_delete_cache" />';
	echo '<div class="submit" style="float:left;margin-left:10px"><input id="deletepost" type="submit" value="Delete Cache" /></div>';
	wp_nonce_field('wp-cache');
	echo "</form>\n";
}

?>