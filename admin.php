<?php
if (is_multisite()) {
  add_action( 'network_admin_menu', 'wptruecache_plugin_menu');
} else {
  add_action( 'admin_menu', 'wptruecache_plugin_menu' );
}
add_action( 'admin_notices', 'wptruecache_plugin_admin_notices' );

add_filter( 'wp_is_large_network', 'wptruecache_is_large_network', 10, 1);

require_once(dirname(__FILE__).'/admin/admin-functions.php');

/*
* Tell other plugins that this site is to be considered a large network (aka high availability)
*/
function wptruecache_is_large_network($using = 'sites') {
	return TRUE;
}

function wptruecache_plugin_admin_notices() {

	if (function_exists('get_memcache_server_list')) {
		$memcached_list = get_memcache_server_list();
		$count = count($memcached_list);
		if ($count == 0 || $count < 0) {
			if (defined('WP_TRUECACHE_ADMIN_NOTICE')) {
				echo WP_TRUECACHE_ADMIN_NOTICE;
			} else {
				echo "<div id='notice' class='updated fade'><p>WARNING: There are no Memcache Servers connected. Please tell the webteam support at inglepatrick@yahoo.com</p></div>\n";				
			}
			//error_log();	
			if (defined('WP_TRUECACHE_EMAILNOTIFY')) wp_mail(WP_TRUECACHE_EMAIL_1,'WARNING: No connections to Memcache Servers','No Memcache server connectivity on '.date('c'));		
		}
	} else {
		echo "<div id='notice' class='updated fade'><p>get_memcache_server_list function is NOT defined!</p></div>\n";
	}
}

function wptruecache_plugin_menu() {
	add_menu_page('WP-TureCache Configuration', 'WP-TrueCache Config', 'administrator', 'wp-truecache/admin/admin-index.php', '', plugins_url('wp-truecache/images/logo.png'));
	add_submenu_page( 'wp-truecache/admin/admin-index.php', 'Memcache', 'Memcache', 'administrator', 'memcache', 'admin_memcache' );
	add_submenu_page( 'wp-truecache/admin/admin-index.php', 'File Cache', 'File Cache', 'administrator', 'filecache', 'admin_filecache' );
	add_submenu_page( 'wp-truecache/admin/admin-index.php', 'Help', 'Help', 'administrator', 'help', 'admin_help');
	add_submenu_page( 'wp-truecache/admin/admin-index.php', 'PHP Info', 'PHP Info', 'administrator', 'phpinfo', 'admin_phpinfo');

}

include(dirname(__FILE__).'/admin/admin-memcache.php');

function admin_memcache() {
	if (function_exists('wptruecache_wpmm_main_page')) wptruecache_wpmm_main_page();
}

include(dirname(__FILE__).'/admin/admin-filecache.php');
function admin_filecache() {
	echo '<a name="top"></a>';
	echo '<div class="wrap">';
	echo '<h2>' . __( 'File Cache Manager', 'filecache' ) . '</h2>';

	if ($_GET['action'] == 'delete') {
		$fc = new FileCache();
		$fc->flush();	
		echo "<div id='notice' class='updated fade'><p>File cache has been deleted</p></div>\n";	
	}

	$tabs = array( 'easy' => __( 'Easy', 'filecache' ), 'contents' => __( 'Contents', 'filecache' ) );
	$page = 'filecache';
	wptruecache_admin_tabs($tabs,$page);

?>
	<table><td valign='top'>
<?php
	switch( $_GET[ 'tab' ] ) {
		default:
		case 'easy':
			//if ( $cache_enabled ) {
/*
				echo "<h3>" . __( 'Cache Tester', 'filecache' ) . "</h3>";
				echo '<p>' . __( 'Test your cached website by clicking the test button below.', 'wp-super-cache' ) . '</p>';
				if ( array_key_exists('action', $_POST) && $_POST[ 'action' ] == 'test' && $valid_nonce ) {
					$url = trailingslashit( get_bloginfo( 'url' ) );
					if ( isset( $_POST[ 'httponly' ] ) )
						$url = str_replace( 'https://', 'http://', $url );
					// Prime the cache
					echo "<p>" . sprintf(  __( 'Fetching %s to prime cache: ', 'wp-super-cache' ), $url );
					$page = wp_remote_get( $url, array('timeout' => 60, 'blocking' => true ) );
					echo '<strong>' . __( 'OK', 'filecache' ) . '</strong></p>';
					sleep( 1 );
					// Get the first copy
					echo "<p>" . sprintf(  __( 'Fetching first copy of %s: ', 'wp-super-cache' ), $url );
					$page = wp_remote_get( $url, array('timeout' => 60, 'blocking' => true ) );
					$fp = fopen( $cache_path . "1.html", "w" );
					fwrite( $fp, $page[ 'body' ] );
					fclose( $fp );
					echo '<strong>' . __( 'OK', 'filecache' ) . "</strong> (<a href='" . WP_CONTENT_URL . "/cache/1.html'>1.html</a>)</p>";
					sleep( 1 );
					// Get the second copy
					echo "<p>" . sprintf(  __( 'Fetching second copy of %s: ', 'wp-super-cache' ), $url );
					$page2 = wp_remote_get( $url, array('timeout' => 60, 'blocking' => true ) );
					$fp = fopen( $cache_path . "2.html", "w" );
					fwrite( $fp, $page2[ 'body' ] );
					fclose( $fp );
					echo '<strong>' . __( 'OK', 'filecache' ) . "</strong> (<a href='" . WP_CONTENT_URL . "/cache/2.html'>2.html</a>)</p>";

					if ( is_wp_error( $page ) || is_wp_error( $page2 ) || $page[ 'response' ][ 'code' ] != 200 || $page2[ 'response' ][ 'code' ] != 200 ) {
						echo '<p><strong>' . __( 'One or more page requests failed:', 'wp-super-cache' ) . '</strong></p>';
						$error = false;
						if ( is_wp_error( $page ) ) {
							$error = $page;
						} elseif ( is_wp_error( $page2 ) ) {
							$error = $page2;
						}
						if ( $error ) {
							$errors = '';
							$messages = '';
							foreach ( $error->get_error_codes() as $code ) {
								$severity = $error->get_error_data($code);
								foreach ( $error->get_error_messages( $code ) as $err ) {
									$errors .= '	' . $err . "<br />\n";
								}
							}
							if ( !empty($err) )
								echo '<div class="updated fade">' . $errors . "</div>\n";
						} else {
							echo '<ul><li>' . sprintf( __( 'Page %d: %d (%s)', 'filecache' ), 1, $page[ 'response' ][ 'code' ], $page[ 'response' ][ 'message' ] ) . '</li>';
							echo '<li>' . sprintf( __( 'Page %d: %d (%s)', 'filecache' ), 2, $page2[ 'response' ][ 'code' ], $page2[ 'response' ][ 'message' ] ) . '</li></ul>';
						}
					}

					if ( preg_match( '/(Cached page generated by WP-Super-Cache on) ([0-9]*-[0-9]*-[0-9]* [0-9]*:[0-9]*:[0-9]*)/', $page[ 'body' ], $matches1 ) &&
							preg_match( '/(Cached page generated by WP-Super-Cache on) ([0-9]*-[0-9]*-[0-9]* [0-9]*:[0-9]*:[0-9]*)/', $page2[ 'body' ], $matches2 ) && $matches1[2] == $matches2[2] ) {
						echo '<p>' . sprintf( __( 'Page 1: %s', 'wp-super-cache' ), $matches1[ 2 ] ) . '</p>';
						echo '<p>' . sprintf( __( 'Page 2: %s', 'wp-super-cache' ), $matches2[ 2 ] ) . '</p>';
						echo '<p><strong>' . __( 'The timestamps on both pages match!', 'wp-super-cache' ) . '</strong></p>';
					} else {
						echo '<p><strong>' . __( 'The pages do not match! Timestamps differ or were not found!', 'wp-super-cache' ) . '</strong></p>';

					}
				}
				echo '<form name="cache_tester" action="" method="post">';
				echo '<input type="hidden" name="action" value="test" />';
				if ( 'on' == strtolower( $_SERVER['HTTPS' ] ) )
					echo "<input type='checkbox' name='httponly' checked='checked' value='1' /> " . __( 'Send non-secure (non https) request for homepage', 'filecache' );
				echo '<div class="submit"><input type="submit" name="test" value="' . __( 'Test Cache', 'filecache' ) . '" /></div>';
				wp_nonce_field('wp-cache');
				echo '</form>';
			//}
*/
			echo "<h3>" . __( "Delete Cached Pages", 'filecache`' ) . "</h3>";
			echo "<p>" . __( "Cached pages are stored on your server as html and PHP files. If you need to delete them use the button below.", 'filecache' ) . "</p>";
			echo '<form name="wp_cache_content_delete" action="?page=filecache&tab=easy&action=delete" method="post">';
			echo '<input type="hidden" name="wp_delete_cache" />';
			echo '<div class="submit"><input id="deletepost" type="submit" ' . SUBMITDISABLED . 'value="' . __( 'Delete Cache', 'filecache' ) . ' &raquo;" /></div>';
			wp_nonce_field('wp-cache');
			echo "</form>\n";		
		break;
	case 'contents':
		wp_cache_files();
		break;
	}
}

function wptruecache_write_config() {
	
	$generation_date = date('D, d M Y H:i:s');
	
	$output = '<?php ';
	$output .= '/**';
	$output .= ' *	Filename: config.php';
	$output .= ' *';
	$output .= ' *	Description: The custom boot loader';
	$output .= ' * ';
	$output .= ' * 	DO NOT MODIFY! THIS FILE IS AUTOGENERATED.';
	$output .= ' *';
	$output .= ' * Generated on: '.$generation_date.'';
	$output .= ' */';
	$output .= '';
	$output .= 'define( \'WPTRUECACHE_VERSION\',\''.WPTRUECACHE_VERSION.'\');';
	$output .= 'define( \'WPTRUECACHE_CONFIG_DATE\',\''.$generation_date.'\');';
	$output .= '';
	$output .= '// locks time out after 5 seconds';
	$output .= 'define( \'LOCK_TIMEOUT\', 5 );';
	$output .= 'define( \'MEMCACHE_TIMEOUT\', 30000 );';
	$output .= 'define( \'COMMENT_COOKIE_TIMEOUT\', (60 * 60 * 24));';
	$output .= 'define( \'WPTRUECACHE_WAIT\', 5 );';
	$output .= 'define( \'WPTRUECACHE_NOCACHE_ITEMS\', \'png|gif|jpg|js|feed|wp-login|wp-admin\');';
	$output .= 'define( \'WPTRUECACHE_MEMCACHE_PORT\', 11211 );';
	$output .= '';
	$output .= 'if (file_exists(dirname(__FILE__).\'/logging.php\')) {';
	$output .= '	include (dirname(__FILE__).\'/logging.php\');	';
	$output .= '}';
	$output .= '';
	$output .= 'if (file_exists(dirname(__FILE__).\'/cache.php\')) {';
	$output .= '	require_once(dirname(__FILE__).\'/cache.php\');';
	$output .= '}';
	$output .= '';
	$output .= 'if (function_exists("wptruecache_check_cache")) {';
	$output .= '	wptruecache_check_cache();';
	$output .= '}';
	$output .= '?>';
	
	$path = dirname(__FILE__);
	if ($fp = fopen($path.'/config.php','w')) {
		fprintf($fp,$output);
		fclose($fp);
		return "<div id='notice' class='updated fade'><p>Saving Easy Settings...</p></div>\n";
	} else {
		return "<div id='notice' class='updated fade'><p>Could not save settings</p></div>\n";		
	}

}

function wptruecache_admin_help_tabs( $current = 0 ) {
	global $wp_db_version;
	if ( $current == 0 ) {
		if ( isset( $_GET[ 'tab' ] ) ) {
			$current = $_GET[ 'tab' ];
		} else {
			$current = 'easy';
		}
	}
	$tabs = array( 'home' => __( 'Overview', 'help' ), 
				   'changes' => __( 'Changes', 'help' ),
				   'config' => __( 'Configuration', 'help' ),
				   'architecture' => __( 'Architecture', 'help' ),
   				   'topics' => __( 'Other', 'help' ) );
	$links = array();
	foreach( $tabs as $tab => $name ) {
		if ( $current == $tab ) {
			$links[] = "<a class='nav-tab nav-tab-active' href='?page=help&tab=$tab'>$name</a>";
		} else {
			$links[] = "<a class='nav-tab' href='?page=help&tab=$tab'>$name</a>";
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

function admin_help() {
	//echo '<h1>Help</h1>';
	//echo '<hr>';

	$plugin_data = get_plugin_data(dirname(__FILE__).'/wptruecache.php');

	//echo '<h1>WP-TrueCache High Availability Custom Plugin</h1>';
	echo '<h1>'.$plugin_data['Name'].'</h1>';
	echo '<p>'.$plugin_data['Description'].'</p>';
	//echo 'Version: '.WPTRUECACHE_VERSION_LEVEL.'<br>';
	echo 'Version: '.$plugin_data['Version'].'<br>';
	echo 'Last Updated: '.WPTRUECACHE_LAST_REVISION_DATE.'<br>';
	//echo 'Authored by: '.WPTRUECACHE_PLUGIN_AUTHOR.' '.WPTRUECACHE_PLUGIN_AUTHOR_EMAIL.'<br>';
	echo 'Authored by: '.$plugin_data['AuthorName'].' '.WPTRUECACHE_PLUGIN_AUTHOR_EMAIL.'<br>';

	wptruecache_admin_help_tabs();
	
	switch( $_GET[ 'tab' ] ) {
		default:
		case 'home':
			if (WPTRUECACHE_VIDEO_ENABLED == TRUE) {
				$embedCode = '<video width="320" height="240" poster="'.WP_PLUGIN_URL.WPTRUECACHE_VIDEO_POSTER.'" controls="controls"><source src="'.WP_PLUGIN_URL.WPTRUECACHE_VIDEO_SOURCE.'" type="'.WPTRUECACHE_VIDEO_TYPE.'" >Your browser does not support HTML5 Video element!</video>';
			} else {
				$embedCode = '<video width="320" height="240" poster="'.WP_PLUGIN_URL.WPTRUECACHE_VIDEO_POSTER.'">Your browser does not support HTML5 Video element!</video><center><i>Video Tutorial is not available</i></center>';	
			}
			include 'admin/docs/home.html';
			break;
		case 'changes':
			include 'admin/docs/changes.html';
			break;
		case 'config':
			include 'admin/docs/config.html';
			break;
		case 'architecture':
			include 'admin/docs/architecture.html';
			break;
		case 'topics':
			include 'admin/docs/topics.html';
			break;
	}
}

function admin_phpinfo() {
	phpinfo();
}

?>
