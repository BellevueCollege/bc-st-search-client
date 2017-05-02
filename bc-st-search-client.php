<?php
/*
Plugin Name: Swiftype Search Client
Plugin URI: https://github.com/BellevueCollege/
Description: Swiftype search client for BC Website
Author: Bellevue College Integration Team
Version: 0.0.0.1
Author URI: http://www.bellevuecollege.edu
GitHub Plugin URI: BellevueCollege/bc-st-search-client
Text Domain: bcswiftype
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Shortcode
function bcswiftype_shortcode( $sc_config ) {
	$sc_config = shortcode_atts( array(
		'query_peram'      => 'txtQuery',
		'page_num_peram'   => 'pg',
		'site_peram'       => 'site',
		'site_api_key'     => 'wp_site_slug',
		'results_per_page' => 10,
		'engine_url'       => 'http://api.swiftype.com/api/v1/public/engines/search.json',
		'engine_key'       => '',
		'title_len'        => 75,
		'excerpt_len'      => 255,
		'spelling'         => 'always'
	), $sc_config, 'bcswiftype_shortcode' );

	

	require( 'classes/class.view.php' );
	require( 'classes/class.model.php' );
	require( 'classes/class.controller.php' );

	$model = new BCswiftype_Model( $sc_config );
	$controller = new BCswiftype_Controller( $model );
	
	$view = new BCswiftype_View( $model );
	return $view->render_html();

}

add_shortcode( 'bc-swiftype-search', 'bcswiftype_shortcode' );

/** 
 * Enqueue script on pages with shortcode
 * https://mikejolley.com/2013/12/02/sensible-script-enqueuing-shortcodes/
 **/
function bcswiftype_scripts() {

	wp_register_script( 'bcswiftype_script', plugin_dir_url( __FILE__ ) . 'js/bcswiftype.js', array( 'jquery' ), "0.0.0", false );
	wp_register_style( 'bcswiftype_style', plugin_dir_url( __FILE__ ) . 'css/bcswiftype.css', '0.0.0' );
	wp_enqueue_style( 'bcswiftype_style' );
	wp_enqueue_script( 'bcswiftype_script' );
}

add_action('wp_enqueue_scripts', 'bcswiftype_scripts');
