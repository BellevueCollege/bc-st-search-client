<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );
class BCswiftype_Controller {
	private $model;

	/**
	 * Constructor
	 *
	 * Accept model and make it available for future use
	 * Loads query perameters
	 **/
	public function __construct( $model ) {
		$this->model = $model;

		// Get queries and set perams
		$args = array(
			'query' => $this->get_url_peram( $model->get_setting( 'query_peram' ), 'string', '' ),
			'page'  => $this->get_url_peram( $model->get_setting( 'page_num_peram' ), 'int', 1 ),
			'sites' => $this->get_url_peram( $model->get_setting( 'site_peram' ), 'array', false ),
		);

		// Load results if there is a query
		if ( ! $model->get_results() && $args['query'] ) {
			$results = $this->load_results( $args );

			// Check if an error is returned
			if ( is_wp_error( $results ) ) {
				// Store error
				$this->model->store_error( $results );
			} else {
				// Store results
				$this->store_results( $results );

				// Store sites in model, because filters aren't returned by API
				$this->model->add_attributes( array(
					'sites' => $args['sites'],
				) );
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
			$raw = $_GET[ $key ]; // This throws wpcs error- no way around it
			$safe;

			if ( 'array' === $type ) {
				$safe = array_map( 'sanitize_text_field', $raw );

			} elseif ( 'int' === $type ) {
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
						'fallback' => true,
					),
					'body' => array(
						'size' => $args['excerpt_len'],
						'fallback' => true,
					),
				),
			),
			'spelling' => $args['spelling'],
		);

		// Add filters to array if needed
		if ( $args['sites'] ) {
			$postfields_array['filters'] = array(
				'page' => array(
					$args['site_api_key'] => $args['sites'],
				),
			);
		}
		// Do things with WP Instead
		$data_raw = wp_safe_remote_post(
			$args['engine_url'],
			array(
				'method'      => 'POST',
				'timeout'     => 2,
				'redirection' => 3,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'Content-type' => 'application/json',
				),
				'body'        => json_encode( $postfields_array ),
				'compress'    => true,
				'sslverify'   => true,
			)
		);

		return $data_raw;

	}

	/**
     * Store results
     *
     * Accept JSON result data and store it to model
     *
     **/
	protected function store_results( $data_json ) {
		// Decode JSON response
		$data_array = json_decode( wp_remote_retrieve_body( $data_json ), true );

		// Restructure Results
		$results = $data_array['records']['page'];

		$results_processed = array();
		foreach ( $results as $result ) {

			// Check if highlight is available; if not, truncate and use title/body. This shouldn't be needed.
			$title   = wp_kses( ( isset( $result['highlight']['title'] ) ? $result['highlight']['title'] : substr( $result['title'], 0,  $this->model->get_setting( 'title_len' ) ) ), wp_kses_allowed_html( 'post' ) );
			$excerpt = wp_kses( ( isset( $result['highlight']['body'] ) ? $result['highlight']['body'] : '<!--fallback-->' . substr( $result['body'], 0,  $this->model->get_setting( 'excerpt_len' ) ) ), wp_kses_allowed_html( 'post' ) );

			$results_processed[] = array(
				'title'    => $title,
				'excerpt'  => $excerpt,
				'url'      => esc_url( $result['url'] ),
				'updated'  => sanitize_text_field( $result['published_at'] ),
			);
		}

		// Process spelling suggestion
		$spelling = wp_kses( ( isset( $data_array['info']['page']['spelling_suggestion']['text'] ) ? $data_array['info']['page']['spelling_suggestion']['text'] : false ), wp_kses_allowed_html( 'post' ) );


		// Restructure Attributes
		$atts_processed = array(
			'query'         => sanitize_text_field( $data_array['info']['page']['query'] ),
			'current_page'  => intval( $data_array['info']['page']['current_page'] ),
			'num_pages'     => intval( $data_array['info']['page']['num_pages'] ),
			'per_page'      => intval( $data_array['info']['page']['per_page'] ),
			'total_results' => intval( $data_array['info']['page']['total_result_count'] ),
			'errors'        => $data_array['errors'], // should sanatize if possible
			'spelling'      => $spelling,
		);

		// Store to Model
		$this->model->store_data( $results_processed, $atts_processed );
	}
}
