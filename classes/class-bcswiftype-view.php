<?php
defined( 'ABSPATH' ) || die( 'No script kiddies please!' );
class BCswiftype_View {
	private $model;

	/**
	 * Constructor
	 *
	 * Accept model and make it available for future use
	 **/
	public function __construct( $model ) {
		$this->model = $model;
	}

	/**
	 * Render HTML
	 *
	 * Generate HTML output for search and return it
	 **/
	public function render_html() {
		// Print-ready version of query
		$query = stripslashes( $this->model->get_attribute( 'query' ) );

		// Instantiate output
		$output = '';

		// Errors
		$output .= $this->render_api_errors( $this->model->get_attribute( 'errors' ) );

		// Searchbox html
		$output .= $this->render_searchbox( $query, $this->model->get_attribute( 'sites' ) );

		// Results html
		if ( $this->model->get_attribute( 'query' ) ) {
			$output .= '<p style="margin-top: 1em">Found ' . $this->model->get_attribute( 'total_results' ) . ' results for <strong>' .
				$query . '</strong> ' . $this->render_sites_filter( $this->model->get_attribute( 'sites' ) ) . '</p>';

			if ( $this->model->get_results() ) {
				$output .= '<div id="results-container">' . $this->render_results() . '</div>';

				// Pagination html
				if ( $this->model->get_attribute( 'total_results' ) > $this->model->get_attribute( 'per_page' ) ) {
					$output .= $this->render_pagination();
				}
			} else {
				// No results message
				$output .= '<div class="jumbotron"><p><strong>Hmmm...</strong> We weren\'t able to find anything related to "' . $this->model->get_attribute( 'query' ) . '". Please re-word your search and try again.</p></div>';
			}
		}

		// Return everything
		return $output;

	}

	/**
	 * Render Pagination
	 *
	 * Render pagination HTML
	 **/
	protected function render_pagination() {
		$atts = $this->model->get_attributes();
		$prev_html;
		$next_html;

		// Build HTML
		if ( $atts['current_page'] > 1 ) {
			$prev_html = '<li><a href="' . $this->page_url( $atts['current_page'] - 1, false ) . '"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Previous</a></li> ';
		} else {
			$prev_html = '<li class="disabled"><a><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span> Previous</a></li> ';
		}

		if ( $atts['current_page'] < $atts['num_pages'] ) {
			$next_html = '<li><a href="' . $this->page_url( $atts['current_page'] + 1, false ) . '">Next <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></a></li>';
		} else {
			$next_html = '<li class="disabled"><a>Next <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span></a></li>';
		}
		return  '<nav aria-label="Search Pagination"><ul class="pager">' .
		$prev_html . $next_html .
		'</ul></nav>';
	}

	/**
	 * Render results
	 *
	 * Get results from the model and render them as HTML
	 **/
	protected function render_results() {
		$result_list = '';
		if ( $this->model->get_results() ) {
			foreach ( $this->model->get_results() as $result ) {
				$title = $result['title'];
				$body = $result['excerpt'];
				$url = $result['url'];
				$updated = date( 'F d, Y', strtotime( $result['updated'] ) );
				$result_list .= "<h2><a href='$url'>$title</a></h2><p class='text-success'>$updated &mdash; $url</p><p>$body</p>";
			}
			return $result_list;
		} else {
			return false;
		}
	}

	/**
	 * Render Searchbox
	 *
	 * Accept search query and output searchbox HTML
	 **/
	protected function render_searchbox( $query = '', $filters ) {
		$filter_tags = '';
		$script_perams = 'engineKey: "' . $this->model->get_setting( 'engine_key' ) . '"';
		if ( is_array( $filters ) ) {
			// Build hidden inputs to preserve filter status
			foreach ( $filters as $filter ) {
				$filter_tags .= '<input type="hidden" name="' . $this->model->get_setting( 'site_peram' ) . '[]" value="' . $filter . '" />';
			}

			//Build filter js perams
			$script_perams = $script_perams .
			', filters: {"page": {"' . $this->model->get_setting( 'site_api_key' ) . '" : ["' . implode( '", "', $filters ) . '"]}} ';
		}

		return <<<HTML
		<form method="GET">
			<label class="sr-only" for="st-search-input">Search</label>
			<div class="input-group input-group-lg">
				{$filter_tags}
				<input id="st-search-input" type="search" class="form-control" name="{$this->model->get_setting( 'query_peram' )}" value="{$query}" placeholder="What can we help you find?" />
				<span class="input-group-btn">
					<button class="btn btn-primary" type="submit">Search</button>
				</span>
			</div>
		</form>
		<script type="text/javascript">
			(function ($) {
				$('#st-search-input').swiftype({ 
					{$script_perams}
				});
			})(jQuery);
		</script>
HTML;
	}

	/**
	 * Page URL
	 *
	 * Accepts page number, and handles generating next and previous page URLs
	 **/
	protected function page_url( $page, $strip_sites = false ) {
		$query = $_GET;
		// replace parameter(s)
		$query[ $this->model->get_setting( 'page_num_peram' ) ] = $page;

		// strip sites perameters if requested
		if ( $strip_sites && isset( $query[ $this->model->get_setting( 'site_peram' ) ] ) ) {
			unset( $query[ $this->model->get_setting( 'site_peram' ) ] );
		}
		// rebuild url
		$query_result = http_build_query( $query );
		// new link
		return '?' . htmlentities( $query_result );
	}

	/**
	 * Render Sites Filter
	 *
	 * Accept array and output html
	 **/
	protected function render_sites_filter( $sites ) {
		if ( $sites ) {
			$output = 'on the sites: ';
			foreach ( $sites as $site ) {
				$output .= "<span class='label label-default'>$site</span> ";
			}
			$output .= '<a class="btn btn-default btn-xs" href="' . $this->page_url( 1, true ) . '"<span class=""><span class="glyphicon glyphicon-remove" aria-hidden="true"></span> search all</span></a>';
			return $output;
		}
	}


	/**
	 * Render API Errors
	 *
	 * Logs and outputs error messages
	 **/
	protected function render_api_errors( $errors ) {
		$error_html = '';
		// Look for errors in the model's error array
		if ( is_array( $this->model->errors ) ) {
			foreach ( $this->model->errors as $error ) {
				$error = $error->get_error_message();
				$error_html .= '<p class="alert alert-danger">' . $error . '</p>';
				error_log( "Swiftype Search Error: $error ", 0 );
			}
		}

		// Look for error in the Swiftype API
		if ( is_array( $errors ) ) {
			foreach ( $errors as $error ) {
				$error = print_r( $error, true );
				$error_html .= "<div class='alert alert-warning'><strong>Swiftype API Error:</strong> <pre>$error</pre></div>";
				error_log( "Swiftype Search API Error: $error ", 0 );
			}
		}
		return $error_html;
	}
}
