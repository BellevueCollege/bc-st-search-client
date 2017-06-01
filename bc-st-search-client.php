<?php
/*
Plugin Name: Swiftype Search Client
Plugin URI: https://github.com/BellevueCollege/
Description: Swiftype search client for BC Website
Author: Bellevue College Integration Team
Version: 0.0.0.3
Author URI: http://www.bellevuecollege.edu
GitHub Plugin URI: BellevueCollege/bc-st-search-client
Text Domain: bcswiftype
*/

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

// Shortcode
function bcswiftype_shortcode( $sc_config ) {
	$sc_config = shortcode_atts( array(
		'query_peram'      => 'txtQuery',
		'page_num_peram'   => 'pg',
		'site_peram'       => 'site',
		'site_filter_id'   => 'site_home_url',
		'results_per_page' => 10,
		'engine_url'       => 'http://api.swiftype.com/api/v1/public/engines/search.json',
		'engine_key'       => '',
		'title_len'        => 75,
		'excerpt_len'      => 255,
		'spelling'         => 'always',
	), $sc_config, 'bcswiftype_shortcode' );

	require_once( plugin_dir_path( __FILE__ ) . 'classes/class-bcswiftype-view.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'classes/class-bcswiftype-model.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'classes/class-bcswiftype-controller.php' );

	$model = new BCswiftype_Model( $sc_config );
	$controller = new BCswiftype_Controller( $model );
	$view = new BCswiftype_View( $model );
	return $view->render_html();

}

add_shortcode( 'bc-swiftype-search', 'bcswiftype_shortcode' );

function bcswiftype_scripts() {
	wp_register_style( 'bcswiftype_style', plugin_dir_url( __FILE__ ) . 'css/bcswiftype.css', '0.0.0' );
	wp_enqueue_style( 'bcswiftype_style' );
}

add_action( 'wp_enqueue_scripts', 'bcswiftype_scripts' );
