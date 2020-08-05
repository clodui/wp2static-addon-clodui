<?php

/**
 * Plugin Name:       WP2Static Add-on: Clodui Deployment
 * Plugin URI:        https://www.clodui.com/wordpress/
 * Description:       Clodui deployment add-on for WP2Static.
 * Version:           1.0-alpha-001
 * Author:            Rajeesh
 * Author URI:        https://www.clodui.com
 * License:           MIT
 * Text Domain:       wp2static-addon-clodui
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'WP2STATIC_CLODUI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP2STATIC_CLODUI_VERSION', '1.0-alpha-001' );

if ( file_exists( WP2STATIC_CLODUI_PATH . 'vendor/autoload.php' ) ) {
    require_once WP2STATIC_CLODUI_PATH . 'vendor/autoload.php';
}

function run_wp2static_addon_clodui() {
    $controller = new WP2StaticClodui\Controller();
    $controller->run();
}

register_activation_hook(
    __FILE__,
    [ 'WP2StaticClodui\Controller', 'activate' ]
);

register_deactivation_hook(
    __FILE__,
    [ 'WP2StaticClodui\Controller', 'deactivate' ]
);

run_wp2static_addon_clodui();

