<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
class BCswiftype_Model {
	private $results;
	private $attributes;
	private $settings;
	public  $errors;

	/**
	 * Constructor
	 *
	 * Accept and store settings on instantiation
	 **/
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Store Data
	 *
	 * Accept results and attributes arrays and save them to variables
	 **/
	public function store_data( $results, $attributes ) {
		$this->results  = $results;
		$this->attributes = $attributes;
	}

	/**
	 * Store Attributes
	 *
	 * Accept array of attributes and merge with existing array
	 **/
	public function add_attributes( $attributes ) {
		$this->attributes = array_merge( $attributes, $this->attributes );

	}

	/**
	 * Get Results
	 *
	 * Return results (if available)
	 **/
	public function get_results( ) {
		if ( isset( $this->results ) ) {
			return $this->results;
		} else {
			return false;
		}
	}

	/**
	 * Get Attribute
	 *
	 * Accepts attribute name and returns attribute value
	 * Known attributes: query, current_page, num_pages, per_page, total_results, errors
	 **/
	public function get_attribute( $name ) {
		if ( isset( $this->attributes ) AND array_key_exists( $name, $this->attributes ) ) {
			return $this->attributes[ $name ];
		} else {
			return false;
		}
	}

	/**
	 * Get Attributes
	 *
	 * Returns array of all attributes
	 * Known attributes: query, current_page, num_pages, per_page, total_results, errors
	 **/
	public function get_attributes( ) {
		if ( isset( $this->attributes ) ) {
			return $this->attributes;
		} else {
			return false;
		}
	}

	/**
	 * Get Setting
	 *
	 * Accepts setting name and returns setting value
	 **/
	public function get_setting( $name ) {
		if ( isset( $this->settings ) AND array_key_exists( $name, $this->settings ) ) {
			return $this->settings[ $name ];
		} else {
			return false;
		}
	}

	/**
	 * Get Settings
	 *
	 * Returns settings array
	 **/
	public function get_settings() {
		if ( isset( $this->settings ) ) {
			return $this->settings;
		} else {
			return false;
		}
	}

	/**
	 * Store Error
	 *
	 * Accepts WP_Error object and saves to array of errors
	 **/
	public function store_error( $error ) {
		if ( is_wp_error( $error ) ) {
			$this->errors[] = $error;
		}
	}
}