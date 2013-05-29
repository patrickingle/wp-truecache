<?php
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	echo '<div class="wrap">';
	echo '<h1>WP-TrueCache Configuration</h1>';
	echo '<p>Version: '.WPTRUECACHE_VERSION.'</p>';
	
	//if (defined('WPTRUECACHE_CONFIG_DATE')) echo '<p>WP-TrueCache Configuration last updated on: <b>'.WPTRUECACHE_CONFIG_DATE.'</b></p>';
	
	$tabs = array( 'easy' => __( 'Advanced', 'wptruecache/admin/admin-index.php' ),  'debug' => __( 'Debug', 'wptruecache/admin/admin-index.php' ) );
	$page = 'wptruecache/admin/admin-index.php';
	wptruecache_admin_tabs($tab,$page);

?>
	<form method="post">
	<table><td valign='top'>
<?php
	switch( $_GET[ 'tab' ] ) {
		default:
		case 'easy':
			echo '<br><u>Caching Enabled:</u><br>';
			$wp_cache = (defined('WP_CACHE') ? (WP_CACHE ? 'checked' : '') : '');
			echo '<input type="checkbox" name="wp_cache" '.$wp_cache.'> WP_CACHE<br>';

			if (function_exists('wp_cache_postload')) {
				echo 'Function: <b>wp_cache_postload()</b> EXISTS!<br>';				
			} else {
				echo 'Function: <b>wp_cache_postload()</b> DOES NOT exist<br>';				
			}
			
			$activated = dirname(__FILE__).'/../../../wptruecache-activate.php';
			if (file_exists($activated)) {
				echo 'Plugin activate!<br>';
			} else {
				echo 'Warning: plugin activation is compromise?<br>';
			}
			
			echo '<br><u>Memcache Servers:</u><br>';
			$servers = (defined('WP_MEMCACHE_SERVERS') ? WP_MEMCACHE_SERVERS : '');
			echo 'WP_MEMCACHE_SERVERS <input type="text" name="memcache_servers" value="'.$servers.'" size="80"><br>';
			$memcache_server_port = (defined('WP_MEMCACHE_PORT') ? WP_MEMCACHE_PORT : '');
			echo 'WP_MEMCACHE_PORT <input type="text" name="memcache_server_port" value="'.$memcache_server_port.'" size="10"><br>';
			
			$memcache_full_server_list = (defined('WP_MEMCACHE_FULL_SERVER_LIST') ? WP_MEMCACHE_FULL_SERVER_LIST : '');
			echo 'WP_MEMCACHE_FULL_SERVER_LIST <input type="text" name="fullserverlist" value="'.$memcache_full_server_list.'" size="80"><br><small>(<i>Overrides the WP_MEMCACHE_SERVERS and WP_MEMCACHE_PORT parameters</i>)</small><br>';
			
			echo '<br><u>Memcache Server(s) Status:</u><br>';

			$active_memcache_servers = get_memcache_server_list();
			
			echo '<br>';
			foreach (explode(",",WP_MEMCACHE_SERVERS) as $server) {
				$connected = '<font color="red">DISCONNECTED</font>';
				foreach($active_memcache_servers as $active_server) {
					if (in_array($server,$active_server)) $connected = '<font color="green">CONNECTED</font>';
				}
				echo $server.' - '.$connected.'<br>';
			}
			echo '<br><u>E-mail Notification:</u><br>';
			$checked = (defined('WP_TRUECACHE_EMAILNOTIFY') ? (WP_TRUECACHE_EMAILNOTIFY ? 'checked' : '') : '');
			echo '<input type="checkbox" name="emailnotifyon" '.$checked.'> WP_TRUECACHE_EMAILNOTIFY<br>';
			$email1 = (defined('WP_TRUECACHE_EMAIL_1') ? WP_TRUECACHE_EMAIL_1 : '');
			echo '<i>Email:</i> <input type="text" name="email1" value="'.$email1.'" size="50"><br>';
			echo '<br>';			
			echo '<br><u>Optional Settings:</u><br>';
			$checked = (defined('WP_TRUECACHE_COMPRESSION') ? (WP_TRUECACHE_COMPRESSION ? 'checked' : '') : '');
			echo '<input type="checkbox" name="traceon" '.$checked.'> WP_TRUECACHE_COMPRESSION<br>';
			$value = (defined('WP_TRUECACHE_CACHEFLUSHALL') ? (WP_TRUECACHE_CACHEFLLUSHALL ? 'checked' : '') : '');
			echo '<input type="checkbox" name="cacheflushall" '.$value.'> WP_TRUECACHE_CACHEFLLUSHALL (<i>If flushing entire cache for each page update?</i>)<br>';
			$value = (defined('WP_TRUECACHE_LOCK_TIMEOUT') ? WP_TRUECACHE_LOCK_TIMEOUT : LOCK_TIMEOUT);
			echo '<i>Lock Timeout:</i> <input type="text" name="locktimeout" value="'.$value.'" size="2">&nbsp;(<i>seconds</i>)<br>';
			$value = (defined('WP_TRUECACHE_MEMCACHE_TIMEOUT') ? WP_TRUECACHE_MEMCACHE_TIMEOUT : MEMCACHE_TIMEOUT);
			echo '<i>Memcache Timeout:</i> <input type="text" name="memcachetimeout" value="'.$value.'" size="2">&nbsp;(<i>'.($value/(60*60)).' hours</i>)<br>';
			$value = (defined('WP_TRUECACHE_COMMENT_COOKIE_TIMEOUT') ? WP_TRUECACHE_COMMENT_COOKIE_TIMEOUT : COMMENT_COOKIE_TIMEOUT);
			echo '<i>Comment Cookie Timeout:</i> <input type="text" name="commentcookietimeout" value="'.$value.'" size="2">&nbsp;(<i>'.($value/(60*60)).' hours</i>)<br>';
			$value = (defined('WP_TRUECACHE_WAIT') ? WP_TRUECACHE_WAIT : WPTRUECACHE_WAIT);
			echo '<i>Wait:</i> <input type="text" name="wait" value="'.$value.'" size="2"><br>';
			$value = (defined('WP_TRUECACHE_NOCACHE_ITEMS') ? WP_TRUECACHE_NOCACHE_ITEMS : WPTRUECACHE_NOCACHE_ITEMS);
			echo '<i>No Cache Items:</i> <input type="text" name="nocacheitems" value="'.$value.'" size="80"><br>';
			//echo '<input type="submit" name="save-easy" value="Save"><br>';
			break;
		case 'debug':
			echo '<br><u>Debug Options:</u><br>';	
			$checked = (defined('WP_TRUECACHE_TRACEON') ? (WP_TRUECACHE_TRACEON ? 'checked' : '') : '');
			echo '<input type="checkbox" name="traceon" '.$checked.'> WP_TRUECACHE_TRACEON<br>';
			$checked = (defined('WP_TRUECACHE_HEADERSTATS') ? (WP_TRUECACHE_TRACEON ? 'checked' : '') : '');
			echo '<input type="checkbox" name="headerstats" '.$checked.'> WP_TRUECACHE_HEADERSTATS<br>';
			break;
	}
?>
	</table>
	</form>
	<style>
 	#wptruecache-header {
    float:left;
    width:100%;
    background:yellow;
    font-size:93%;
    line-height:normal;
    }

	#wptruecache-header ul {
    margin:0;
    padding:0;
    list-style:none;
    }
  	#wptruecache-header li {
    float:left;
    margin:0;
    padding:0;
    }
	#wptruecache-header a {
    display:block;
    }	
	</style>
	<div id="wptruecache-header">
  	</div>
	</div>
 <?php



?>