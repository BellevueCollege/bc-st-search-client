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
		'spelling' => 'false',
	), $sc_config, 'bcswiftype_shortcode' );

	//

	// Load and sanitize query
	$query = '';
	$page  = 1;

	if ( isset( $_GET['txtQuery'] ) ) {
		$query = sanitize_text_field( $_GET['txtQuery'] );
	}
	if ( isset( $_GET['pg'] ) ) {
		$page = sanitize_text_field( $_GET['pg'] );
	}

return <<<HTML

<form method="GET">
	<label class="sr-only" for="st-search-input">Search</label>
	<div class="input-group input-group-lg">
		<input id="st-search-input" type="search" class="form-control" name="txtQuery" value="{$query}" placeholder="What can we help you find?" />
		<span class="input-group-btn">
			<button class="btn btn-primary" type="submit">Search</button>
		</span>
	</div>

</form>
<div id="st-results-container" style="margin-top:1em"></div>
<div id="st-pagination-container"></div>
<script type="text/javascript">
jQuery( function() {
	if ( '{$query}' != '' ) {
		ajax_call( '{$query}', '{$page}' );
	}
});


function ajax_call( query, page ) {
	jQuery.ajax({
		url: "http://api.swiftype.com/api/v1/public/engines/search.json",

		// The name of the callback parameter
		jsonp: "callback",
		// Tell jQuery we're expecting JSONP
		dataType: "jsonp",
	
		// Get search results
		data: {
			q: query,
			page: page,
			per_page: 10,
			engine_key: "YUFwdxQ6-Kaa9Zac4rpb",
			highlight_fields:{
				page:{
					title:{
						size:75,
						fallback:true
					},
					body:{
						size:255,
						fallback:true
					},
				},
			},
			fetch_fields:{
				page:[ "url", "published_at"]
			}
		},

		// Work with the response
		success: function( response ) {
			html_results( response );
			
		}
	});
}

function html_results( results ) {

	var result_html = '';

	// Format results and write to DOM
	for ( var i = 0, len = results.records.page.length; i < len; i++ ) {
		var result = results.records.page[i];
		var published_at = new Date( result.published_at );
		var result_single_html = '<div class="result">' +
			'<h2><a href="' + result.url + '" target="_blank">' + result.highlight.title + '</a></h2>' +
			'<p class="text-success">Updated ' + published_at.toLocaleDateString()  + ' &mdash; ' + result.url + '</p>' +
			'<p>' + result.highlight.body + ' ... </p>' +
			'</div>';

		result_html += result_single_html;
	}
	jQuery("#st-results-container").html( result_html );
	jQuery("#st-results-container").prepend( '<p>Found ' + results.info.page.total_result_count + ' results for <strong>' + results.info.page.query + '</strong></p>');

	
	results.info.page.current_page //current page
	// Format pagination and write to DOM
	var prev_page = results.info.page.current_page - 1;
	var next_page = results.info.page.current_page + 1;

	if ( results.info.page.current_page > results.info.page.num_pages ) {
		prev_page = results.info.page.num_pages - 1;
		next_page = results.info.page.num_pages;
	}

	var prev_link = '?txtQuery=' + results.info.page.query + '&pg=' + prev_page;
	var next_link = '?txtQuery=' + results.info.page.query + '&pg=' + next_page;

	var page_prev;
	var page_next;

	if ( results.info.page.current_page > 1 ) {
		page_prev = '<li><a href="' + prev_link + '"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Previous</a></li> ';
	} else {
		page_prev = '<li class="disabled"><a><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Previous</a></li> ';
	}

	if ( results.info.page.current_page < results.info.page.num_pages ) {
		page_next = '<li><a href="' + next_link + '">Next <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></a></li>';
	} else {
		page_next = '<li class="disabled"><a>Next <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></a></li>';
	}

	var pageination_html = '<nav aria-label="Search Pagination"><ul class="pager">' +
		page_prev + page_next +
		'</ul></nav>';

	if ( results.info.page.current_page > 0 ) {
		jQuery("#st-pagination-container").html( pageination_html );
	}
}

</script>

HTML;
}

add_shortcode( 'bc-swiftype-search', 'bcswiftype_shortcode' );

/** 
 * Enqueue script on pages with shortcode
 * https://mikejolley.com/2013/12/02/sensible-script-enqueuing-shortcodes/
 **/
function bcswiftype_scripts() {
	wp_register_script( 'bcswiftype_script', plugin_dir_url( __FILE__ ) . 'js/bcswiftype.js', array( 'jquery' ), "0.0.0", true );
	wp_register_style( 'bcswiftype_style', plugin_dir_url( __FILE__ ) . 'css/bcswiftype.css' );
	wp_enqueue_style( 'bcswiftype_style' );
	wp_enqueue_script( 'bcswiftype_script' );
}

add_action( 'wp_enqueue_scripts', 'bcswiftype_scripts' );

