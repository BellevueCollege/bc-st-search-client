<?php
/*
Plugin Name: Swiftype Search Client
Plugin URI: https://github.com/BellevueCollege/bc-st-search-client
Description: Swiftype search client for BC Website
Author: Bellevue College Integration Team
Version: 1.1.0-dev.2
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
		'engine_url'       => 'https://api.swiftype.com/api/v1/public/engines/search.json',
		'engine_key'       => '',
		'localstorage_key' => 'searchHistory',
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

	// Add inline js attributes for script to use
	$filter_array = is_array( $model->get_attribute( 'sites' ) ) ? '["' . implode( '", "', $model->get_attribute( 'sites' ) ) . '"]': 'false';
	wp_add_inline_script(
		'bcswiftype_script',
		'var st_engine_key = "' . esc_attr( $model->get_setting( 'engine_key' ) ) .
		'"; var localstorage_key = "' . esc_attr( $model->get_setting( 'localstorage_key' ) ) .
		'"; var st_query = "' . esc_html( stripslashes( $model->get_attribute( 'query' ) ) ) .
		'"; var st_site_filter_id = "' . esc_html( $model->get_setting( 'site_filter_id' ) ) .
		'"; var st_filter_array = "' . esc_html( $filter_array ) . '";', 'before'
	);

	return $view->render_html();
}

add_shortcode( 'bc-swiftype-search', 'bcswiftype_shortcode' );

function bcswiftype_scripts() {
	wp_register_style( 'bcswiftype_style', plugin_dir_url( __FILE__ ) . 'css/bcswiftype.css', '1.0.1' );
	wp_enqueue_style( 'bcswiftype_style' );
	wp_enqueue_script( 'bcswiftype_script', plugin_dir_url( __FILE__ ) . 'js/bcswiftype.js', array( 'globals' ), '1.0.1', true );
}

add_action( 'wp_enqueue_scripts', 'bcswiftype_scripts' );

// Register API
/**
 * Initiate REST API
 *
 * Register the REST routes
 */

add_action( 'rest_api_init' , 'bcswiftype_rest_register_routes' );

function bcswiftype_rest_register_routes( ) {
	$version = '1';
	$namespace = 'bcswiftype/v' . $version; //declares the home route of the REST API
	//registered route tells the API to respond to a given request with a callback function
	//this is one route with one endpoint method GET requesting a parameter ID on the URL
	register_rest_route( $namespace, '/autofill/', array(
		'methods' => 'GET',
		'callback' => 'bcswiftype_autofill',
		'args' => array(
			'id' => array(
			'validate_callback' => function($param, $request, $key) {
				return is_numeric( $param );
			}
			),
		),
	) );
}

function bcswiftype_autofill( WP_REST_Request $request ) {
	$postfields_array = array(
		'q'          => $request->get_param( 'q' ),
		'per_page'   => $request->get_param( 'per_page' ),
		'engine_key' => $request->get_param( 'engine_key' ),
		'filters' => $request->get_param( 'filters' ),
	);
		
	$af_data_raw = '';

	// Create hash of query
	$query_hash = 'bcstscaf_' . hash("crc32b", json_encode( $postfields_array ) );

	// Get cached query results based on hash (false if none)
	$cached_query = get_transient( $query_hash );
	
	// If query is cached, use the cache. If not, set cache.
	if ( $cached_query ) {
		$af_data_raw = $cached_query;

	} elseif ( strlen( $postfields_array['q'] ) > 2) {
		$af_data_raw = wp_safe_remote_post(
			'https://api.swiftype.com/api/v1/public/engines/suggest.json',
			array(
				'method'      => 'POST',
				'timeout'     => 2,
				'redirection' => 3,
				'headers'     => array(
					'Content-type' => 'application/json',
				),
				'body'        => json_encode( $postfields_array ),
				'compress'    => true,
				'sslverify'   => true,
			)
		);
		set_transient( $query_hash, $af_data_raw, 60 * 60 * 24 ); // set for 24 hours
	}
	return json_decode( wp_remote_retrieve_body( $af_data_raw ), true );
}