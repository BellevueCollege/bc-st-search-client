<?php 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
class BCswiftype_Controller {
	private $model;

	/**
	 * Constructor
	 *
	 * Accept model and make it available for future use
	 * Loads query perameters, 
	 **/
	public function __construct( $model ) {
		$this->model = $model;

		// Get queries
		$query = $this->get_url_peram( $model->get_setting( 'query_peram' ), 'string', '' );
		$page  = $this->get_url_peram( $model->get_setting( 'page_num_peram' ), 'int', 1 );
		$sites = $this->get_url_peram( $model->get_setting( 'site_peram' ), 'array', false );

		// Perams
		$args = array( 
			'query' => $query,
			'page'  => $page,
			'sites' => $sites,
		);

		// Load results if there is a query
		if ( !$model->get_results() AND $args[ 'query' ] ) {
			$results = $this->load_results( $args );

			// Check if an error is returned
			if ( is_wp_error( $results ) ) {
				// Store error
				$this->model->store_error( $results );
			} else {
				// Store results
				$this->store_results( $results );

				// Store sites in model, because filters aren't returned by API
				$this->model->add_attributes( array( 'sites' => $args['sites'] ) );
			}
		}
	}

	/**
	 * Get URL Peram
	 *
	 * Accept perameter key, type (array or int, defaults to string),
	 * and a default value to return. 
	 *
	 * Looks up URL peram by key and returns it if it exists and is valid.
	 *
	 * Truncates to prevent overly long queries and page numbers. 
	 *
	 **/
	protected function get_url_peram( $key, $type, $default = false ) {
		if ( isset( $_GET[ $key ] ) ) {
			$raw = $_GET[ $key ];
			$safe;

			if ( $type == 'array' ) {
				$safe = array_map( 'sanitize_text_field', $raw );

			} else if ( $type == 'int' ) {
				$safe = intval( $raw );
				if ( $safe > 256 ) { // Arbitrary number
					$safe = 256;
				}

			} else {
				$safe = sanitize_text_field( $raw );

				if ( strlen( $safe ) > 255 ) {
					$safe = substr( $safe, 0, 255 );
				}
			}
			return $safe; 
		} else {
			return $default;
		}
	}

	/**
	 * Load Results
	 *
	 * Accepts arguments and returns JSON from API
	 *
	 * This is the guts of the application - changing target API happens here
	 *  
	 **/
	public function load_results( $args ) {

		// Merge settings array with passed arguments
		$args = array_merge( $this->model->get_settings(), $args );

		// Values to be passed to Swiftype
		$postfields_array = array(
			'q'          => stripslashes( $args['query'] ),
			'page'       => $args['page'],
			'per_page'   => $args['results_per_page'],
			'engine_key' => $args['engine_key'],
			'highlight_fields' => array(
				'page' => array(
					'title' => array(
						'size' => $args['title_len'],
						'fallback' => true
					),
					'body' => array(
						'size' => $args['excerpt_len'],
						'fallback' => true
					)
				)
			),
			'spelling' => $args['spelling']
		);

		// Add filters to array if needed
		if ( $args['sites'] ) {
			$postfields_array['filters'] = array(
				'page' => array(
					$args['site_api_key'] => $args['sites']
				)
			);
		}
		
		// Encode as JSON before sending
		$postfields = json_encode( $postfields_array );

		// Load from API via CURL
		$curl = curl_init();
		curl_setopt_array( $curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 2, 
			CURLOPT_URL            => $args['engine_url'],
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
			CURLOPT_POSTFIELDS     => $postfields,
			CURLOPT_FAILONERROR    => true
		));

		// Get data
		$data_raw = curl_exec( $curl );

		// Catch CURL errors
		if ( curl_error( $curl ) ) {
			return new WP_Error( 'curl_error', __( 'CURL Error: ' . curl_error( $curl ), 'bcswiftype' ) );
		}

		// Close connection
		curl_close( $curl );

		// Decode JSON to array
		return json_decode( $data_raw, true );

	}

	/**
     * Store results
     *
     * Accept JSON result data and store it to model
     *
     **/
	protected function store_results( $data_json ) {
		// Restructure Results
		$results = $data_json['records']['page'];

		$results_processed = array();
		foreach ( $results as $result ) {

			// Check if highlight is available; if not, truncate and use title/body. This shouldn't be needed.
			$title   = wp_kses( ( isset( $result['highlight']['title'] ) ? $result['highlight']['title'] : substr( $result['title'], 0,  $this->model->get_setting( 'title_len' ) ) ), wp_kses_allowed_html( 'post' ) );
			$excerpt = wp_kses( ( isset( $result['highlight']['body'] ) ? $result['highlight']['body'] : '<!--fallback-->' .substr( $result['body'], 0,  $this->model->get_setting( 'excerpt_len' ) ) ), wp_kses_allowed_html( 'post' ) );

			$results_processed[] = array(
				'title'    => $title,
				'excerpt'  => $excerpt,
				'url'      => esc_url( $result['url'] ),
				'updated'  => sanitize_text_field( $result['published_at'] )
			);
		}

		// Restructure Attributes
		$atts_processed = array(
			'query'         => sanitize_text_field( $data_json['info']['page']['query'] ),
			'current_page'  => intval( $data_json['info']['page']['current_page'] ),
			'num_pages'     => intval ($data_json['info']['page']['num_pages'] ),
			'per_page'      => intval ($data_json['info']['page']['per_page'] ),
			'total_results' => intval ($data_json['info']['page']['total_result_count'] ),
			'errors'        => $data_json['errors']
		);

		// Store to Model
		$this->model->store_data( $results_processed, $atts_processed );
	}
}
