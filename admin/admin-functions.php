<?php

function wptruecache_admin_tabs( $tabs, $page, $current = 0 ) {
	global $wp_db_version;
	if ( $current == 0 ) {
		if ( isset( $_GET[ 'tab' ] ) ) {
			$current = $_GET[ 'tab' ];
		} else {
			$current = 'easy';
		}
	}
	//$tabs = array( 'easy' => __( 'Advanced', 'wptruecache/admin/admin-index.php' ),  'debug' => __( 'Debug', 'wptruecache/admin/admin-index.php' ) );
	$links = array();
	if (is_array($tabs)) {		
		foreach( $tabs as $tab => $name ) {
			if ( $current == $tab ) {
				//$links[] = "<a class='nav-tab nav-tab-active' href='?page=wptruecache/admin/admin-index.php&tab=$tab'>$name</a>";
				$links[] = "<a class='nav-tab nav-tab-active' href='?page=$page&tab=$tab'>$name</a>";
			} else {
				//$links[] = "<a class='nav-tab' href='?page=wptruecache/admin/admin-index.php&tab=$tab'>$name</a>";
				$links[] = "<a class='nav-tab' href='?page=$page&tab=$tab'>$name</a>";
			}
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

?>