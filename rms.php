<?php
/*
Plugin Name: Remote Image Saver
Plugin URI: https://phamviet.net
Description: Parse remote image links on saving to download and store it in local server.
Version: 1.0
Author: Viet Pham
Author URI: https://phamviet.net
License: GPL
Copyright: Viet Pham
Text Domain: rms
Domain Path: /lang
*/

// exit
defined( 'ABSPATH' ) OR exit;

// constants
define( 'RMS_FILE', __FILE__ );
define( 'RMS_DIR', dirname( __FILE__ ) );
define( 'RMS_SLUG', 'remote-image-saver' );

// hooks
add_action(
	'plugins_loaded',
	[
		'Remote_Image_Saver',
		'instance',
	]
);
register_activation_hook(
	__FILE__,
	[
		'Remote_Image_Saver',
		'on_activation',
	]
);
register_deactivation_hook(
	__FILE__,
	[
		'Remote_Image_Saver',
		'on_deactivation',
	]
);
register_uninstall_hook(
	__FILE__,
	[
		'Remote_Image_Saver',
		'on_uninstall',
	]
);

// autoload register
spl_autoload_register( 'rms_autoload' );

// autoload function
function rms_autoload( $class ) {
	if ( in_array( $class, [ 'Remote_Image_Saver' ] ) ) {
		require_once(
		sprintf(
			'%s/inc/%s.class.php',
			RMS_DIR,
			strtolower( $class )
		)
		);
	}
}