# BC Swiftype Search Client
Bellevue College Client for Swiftype Search

## Setup
Install as a normal plugin. If installed on a multisite environment, it should be **single site activated** on the site on which it will be used.

The search shortcode should be added as the only item on a page, preferably on the homepage of the site. In production, it will be on the homepage of the site www.bellevuecollege.edu/search/.

## Shortcode
```
[bc-swiftype-search engine_key="XXXXXXXXXXXXXXXXXXXX"]
``` 
### Available shortcode attributes
The following array (located in bc-st-search-client.php) contains all shortcode attributes, and their default values.

```php
array(
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
)

```

* `query_peram`: URL perameter used to store search query
* `page_num_peram`: URL perameter used to store current page number
* `site_peram`: Array perameter used to store site filters
* `site_api_key`: Perameter in Swiftype API that you would like the site filter to compare against
* `results_per_page`: How many results to show on each page
* `engine_url`: URL the plugin should use to access Swiftype's API. This should remain the same for all public search engines.
* `engine_key`: Public API key
* `title_len`: Max number of characters allowed in search result titles
* `excerpt_len`: Max number of characters allowed in search result excerpts
* `spelling`: Behavior of spell checking- accepts `strict`, `always`, and `retry`. See Swiftype documentation for behavior. **NOTE:** Spell checking is not fully implimented at this time, as it is not a feature that is active on our trial plan.

## Search Query URLs
Certain functionality (namely filtering by site) can only be accessed by creating a custom URL. 

**Note: the structure of strings accepted by the 'site' perameter will changed once we go live. Currently it accepts the site 'slug', this will change the site URL minus the protocol.**

**Examples of change:** 
Currently to restrict searches to https://www.bellevuecollege.edu/abe/ one would use the `site` attribute "abe". In the future, this will probably use the attribute "www.bellevuecollege.edu/abe" 

### URL Examples:

Search for 'Test Query' on the sites 'abe' and 'enrollement':
* https://www.bellevuecollege.edu/search/?txtQuery=Test+Query&site[]=abe&site[]=enrollment

Search for 'Test Query' on all sites:
* https://www.bellevuecollege.edu/search/?txtQuery=Test+Query

Get second page of results to 'Test Query':
* https://www.bellevuecollege.edu/search/?txtQuery=Test+Query&pg=2
