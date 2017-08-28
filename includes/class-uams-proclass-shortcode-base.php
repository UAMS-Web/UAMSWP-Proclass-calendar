<?php
/**
 * A base class for UAMS syndicate shortcodes.
 *
 * Class UAMS_Proclass_Shortcode_Base
 */
class UAMS_Proclass_Shortcode_Base {
	/**
	 * Default path used to consume the REST API from an individual site.
	 *
	 * @var string
	 */
	public $default_path = 'api/';

	/**
	 * Default attributes applied to all shortcodes that extend this base.
	 *
	 * @var array
	 */
	public $defaults_atts = array(
		'object' => 'json_data',
		'output' => 'headlines',
		'host' => 'api111.imperisoft.com',
		'scheme' => 'https',
		'site' => '',
		'location' => '',
		'query' => 'programlist',
		'count' => false,
		'date_format' => 'F j, Y',
		'cache_bust' => '',
	);

	/**
	 * Defaults for individual base attributes can be overridden for a
	 * specific shortcode.
	 *
	 * @var array
	 */
	public $local_default_atts = array();

	/**
	 * Defaults can be extended with additional keys by a specific shortcode.
	 *
	 * @var array
	 */
	public $local_extended_atts = array();

	/**
	 * @var string The shortcode name.
	 */

	public $shortcode_name = '';

	/**
	 * A common constructor that initiates the shortcode.
	 */
	public function construct() {
		$this->add_shortcode();
	}

	/**
	 * Required to add a shortcode definition.
	 */
	public function add_shortcode() {}

	/**
	 * Required to display the content of a shortcode.
	 *
	 * @param array $atts A list of attributes assigned to the shortcode.
	 *
	 * @return string Final output for the shortcode.
	 */
	public function display_shortcode( $atts ) {
		return '';
	}

	/**
	 * Enqueue the mapping scripts and styles when a page with the proper shortcode tag is being displayed.
	 */
	public function enqueue_proclass_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'proclass_calendar' ) ) {
		wp_register_style( 'uamswp-proclass-calendar', plugins_url('/css/uamswp-proclass-calendar.css', __FILE__ ), array(), false );
		}
	}

	/**
	 * Process passed attributes for a shortcode against arrays of base defaults,
	 * local defaults, and extended local defaults.
	 *
	 * @param array $atts Attributes passed to a shortcode.
	 *
	 * @return array Fully populated list of attributes expected by the shortcode.
	 */
	public function process_attributes( $atts ) {
		$defaults = shortcode_atts( $this->defaults_atts, $this->local_default_atts );
		$defaults = $defaults + $this->local_extended_atts;

		return shortcode_atts( $defaults, $atts, $this->shortcode_name );
	}

	/**
	 * Create a hash of all attributes to use as a cache key. If any attribute changes,
	 * then the cache will regenerate on the next load.
	 *
	 * @param array  $atts      List of attributes used for the shortcode.
	 * @param string $shortcode Shortcode being displayed.
	 *
	 * @return bool|string False if cache is not available or expired. Content if available.
	 */
	public function get_content_cache( $atts, $shortcode ) {
		$atts_key = md5( serialize( $atts ) ); // @codingStandardsIgnoreLine

		$content = wp_cache_get( $atts_key, $shortcode );

		return $content;
	}

	/**
	 * Store generated content from the shortcode in cache.
	 *
	 * @param array  $atts      List of attributes used for the shortcode.
	 * @param string $shortcode Shortcode being displayed.
	 * @param string $content   Generated content after processing the shortcode.
	 */
	public function set_content_cache( $atts, $shortcode, $content ) {
		$atts_key = md5( serialize( $atts ) ); // @codingStandardsIgnoreLine

		wp_cache_set( $atts_key, $content, $shortcode, 600 );
	}

	/**
	 * Processes a given site URL and shortcode attributes into data to be used for the
	 * request.
	 *
	 * @since 0.10.0
	 *
	 * @param array $site_url Contains host and path of the requested URL.
	 * @param array $atts     Contains the original shortcode attributes.
	 *
	 * @return array List of request information.
	 */
	public function build_initial_request( $site_url, $atts ) {
		$url_scheme = 'https';

		$request_url = esc_url( $url_scheme . '://' . $site_url['host'] . $site_url['path'] . $this->default_path ) . $atts['query'];

		$request = array(
			'url' => $request_url,
			'scheme' => $atts['scheme'],
		);

		return $request;
	}

	/**
	 * Determine what the base URL should be used for REST API data.
	 *
	 * @param array $atts List of attributes used for the shortcode.
	 *
	 * @return bool|array host and path if available, false if not.
	 */
	public function get_request_url( $atts ) {
		// If a site attribute is provided, it overrides the host attribute.
		if ( ! empty( $atts['site'] ) ) {
			$site_url = trailingslashit( esc_url( $atts['site'] ) );
		} else {
			$site_url = trailingslashit( esc_url( $atts['host'] ) );
		}

		$site_url = wp_parse_url( $site_url );
		if ( empty( $site_url['host'] ) ) {
			return false;
		}

		return $site_url;
	}

	/**
	 * Add proper filters to a given URL to handle lookup by University taxonomies and
	 * built in WordPress taxonomies.
	 *
	 * @param array  $atts        List of attributes used for the shortcode.
	 * @param string $request_url REST API URL being built.
	 *
	 * @return string Modified REST API URL.
	 */
	public function build_taxonomy_filters( $atts, $request_url ) {

		if ( ! empty( $atts['location'] ) ) {
			$location = rawurlencode( $atts['location'] );
			$request_url = add_query_arg( array(
				'$filter' => "substringof('Scheduled',StatusDescription)%20eq%20true%20and%20substringof('". $location ."',ShortDescription)%20eq%20true",
				'$orderby'=> 'StartDate',
			), $request_url );
		} else {
			$request_url = add_query_arg( array(
				'$filter' => "substringof('Scheduled',StatusDescription)%20eq%20true",
				'$orderby'=> 'StartDate',
			), $request_url );
		}

		return $request_url;
	}

	public function get_api_auth() {

		$options = get_option( 'uamswp_proclass_settings' );
		if(isset($options['uamswp_proclass_authentication_user']) && !empty($options['uamswp_proclass_authentication_user'])){
			$authuser = sanitize_text_field($options['uamswp_proclass_authentication_user']);
		} else {
			// Set Default Value, if desired
			$authuser = 'InsertDefaultValue';
		}
		if(isset($options['uamswp_proclass_authentication_pass']) && !empty($options['uamswp_proclass_authentication_pass'])){
			$authpass = sanitize_text_field($options['uamswp_proclass_authentication_pass']);
		} else {
			// Set Default Value, if desired
			$authpass = 'InsertDefaultValue';
		}

		$auth = array(
		  'headers' => array(
		    'Authorization' => 'Basic ' . base64_encode( $authuser . ':' . $authpass )
		  )
		);
		//echo $authuser . ':' . $authpass;
		return $auth;
	}

}