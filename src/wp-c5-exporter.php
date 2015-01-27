<?php
/*
Plugin Name: WP C5 Exporter
Description: Move your WordPress blog content to your concrete5 site.
Version: 0.1
License: GPLv2 or later
Author: hissy
Author URI: http://notnil-creative.com
*/

if ( ! defined( 'WPINC' ) ) exit;

define( 'WP_C5_EXPORTER_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_C5_EXPORTER_PLUGIN_REL_PATH', plugin_basename(__FILE__) );
define( 'WP_C5_EXPORTER_PLUGIN_DOMAIN', 'wp-c5-exporter' );

if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	deactivate_plugins( plugin_basename( __FILE__ ) );
	wp_die( esc_html__( 'WP C5 Exporter requires PHP version 5.3.0 or later.', WP_C5_EXPORTER_PLUGIN_DOMAIN ) );
}

require dirname( __FILE__ ) . '/vendor/autoload.php';

foreach( glob( WP_C5_EXPORTER_PLUGIN_DIR_PATH. 'class-*.php' ) as $class ) {
	require $class;
}

function wp_c5_exporter_init() {
	return WP_C5_Exporter_Admin::instance();
}

add_action( 'plugins_loaded', 'wp_c5_exporter_init' );
