( function ( $ ) {
	//Ensure variables are defined
	if ( typeof st_engine_key     !== 'undefined' ||
		 typeof st_query          !== 'undefined' ||
		 typeof st_site_filter_id !== 'undefined' || 
		 typeof st_filter_array   !== 'undefined' ) {

		// Trigger autofill
		if ( st_filter_array === "false" || st_filter_array === false ) {
			$( '#st-search-input' ).swiftype( { 
				engineKey: st_engine_key,
				typingDelay: 600,
				renderFunction: function(document_type, item, idx) {
					return '<p class="title" data-url="'+ item['url'] +'">' + Swiftype.htmlEscape(item['title']) + '</p>';
				}
			} );

			// Search History Dropdown
			$( '#st-search-box' ).searchHistory({
				field: '#st-search-input',
				localStorageKey: localstorage_key
			});
		
		// Trigger autofill with filter(s)
		} else {
			$( '#st-search-input' ).swiftype( { 
				engineKey: st_engine_key,
				filters: {
					"page": {
						st_site_filter_id : st_filter_array
					}
				},
				typingDelay: 600,
				renderFunction: function(document_type, item, idx) {
					return '<p class="title" data-url="'+ item['url'] +'">' + Swiftype.htmlEscape(item['title']) + '</p>';
				}
			} );

			// Search History Dropdown
			$( '#st-search-box' ).searchHistory({
				field: '#st-search-input',
				localStorageKey: localstorage_key + '_' + btoa(st_filter_array) // Base 64 encode filter array and append to localstorage key
			});
		}
		// Send search analytics to Swiftype
		$( 'a.st-result-link' ).click( function( event ) {
			// Stop link from linking
			event.preventDefault();
			// Store link href and data-stid
			var st_result_url = $( this) .attr( 'href' );
			var swiftype_page_id = $( this ).data( 'stid' );
			var st_api_url = "https://api.swiftype.com/api/v1/public/analytics/pc?";
			// Build perameters
			var st_api_params = {
				t: new Date().getTime(), // Timestamp to prevent cache
				engine_key: st_engine_key,
				doc_id: swiftype_page_id,
				document_type_id: 'page',
				q: st_query
			};
			// Build URL with query perams
			var st_full_url = st_api_url + $.param( st_api_params );
			// Call pingUrl, loading link once process completes
			Swiftype.pingUrl( st_full_url, function() { 
				window.location = st_result_url;
			} );
		} );
	}

	/* From https://github.com/swiftype/swiftype-search-jquery/blob/master/jquery.swiftype.search.js#L25
	MIT Licence by Swiftype */
	Swiftype.pingUrl = function( endpoint, callback ) {
		var to = setTimeout( callback, 350 );
		var img = new Image();
		img.onload = img.onerror = function() {
			clearTimeout( to );
			callback();
		};
		img.src = endpoint;
		return false;
	};
} )( jQuery );
