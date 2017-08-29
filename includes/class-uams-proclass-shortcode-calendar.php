<?php

class UAMS_Proclass_Shortcode_Calendar extends UAMS_Proclass_Shortcode_Base {

	/**
	 * @var string Shortcode name.
	 */
	public $shortcode_name = 'proclass_calendar';

	public function __construct() {
		parent::construct();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_proclass_stylesheet' ) );
	}


	/**
	 * Enqueue styles specific to the network admin dashboard.
	 */
	public function enqueue_proclass_stylesheet() {
		//if ( 'dashboard-network' === get_current_screen()->id ) {
			wp_enqueue_style( 'uamswp-proclass-calendar-style', plugins_url( '/css/uamswp-proclass-calendar.css', __DIR__ ), array(), '' );
		//}
	}

	/**
	 * Add the shortcode provided.
	 */
	public function add_shortcode() {
		add_shortcode( 'proclass_calendar', array( $this, 'display_shortcode' ) );
	}

	/**
	 * Process the requested parameters for use with the WordPress JSON API and output
	 * the response accordingly.
	 *
	 * @param array $atts {
	 *     Attributes passed with the shortcode.
	 *
	 *     @type string $object                   The name of the JSON object to use when output is set to json.
	 *     @type string $output                   The type of output to display.
	 *                              - headlines      Display an unordered list of headlines.
	 *                              - excerpts       Display only excerpt information in an unordered list.
	 *                              - minical        Display only mini-calendar with title.
	 *     @type string $host                     The hostname to pull items from. Defaults to api111.imperisoft.com.
	 *     @type string $site                     Overrides setting for host. Hostname and path to pull items from.
	 *     @type string $site_location_slug       The name/text of the site location (ex. 'Pine Bluff'). Defaults to empty.
	 *     @type string $query                    Allows for a custom API query. Defaults as "programlist". Any
	 *     @type int    $count                    The number of items to pull from a feed. Defaults to the
	 *                                            posts_per_page setting of the remote site.
	 *     @type string $date_format              PHP Date format for the output of the item's date.
	 *     @type string $cache_bust               Any change to this value will clear the cache and pull fresh data.
	 * }
	 *
	 * @return string Data to output where the shortcode is used.
	 */
	public function display_shortcode( $atts ) {
		$atts = $this->process_attributes( $atts );

		$site_url = $this->get_request_url( $atts );
		if ( ! $site_url ) {
			return '<!-- proclass_calendar ERROR - an empty host was supplied -->';
		}

		// Retrieve existing content from cache if available.
		$content = $this->get_content_cache( $atts, 'proclass_calendar' );
		if ( $content ) {
			return apply_filters( 'uamswp_proclass_syndication_calendar', $content, $atts );
		}

		$request = $this->build_initial_request( $site_url, $atts );
		$request_url = $this->build_taxonomy_filters( $atts, $request['url'] );

		if ( $atts['count'] ) {
			$count = ( 100 < absint( $atts['count'] ) ) ? 100 : $atts['count'];
			$request_url = add_query_arg( array(
				'$top' => absint( $count ),
			), $request_url );
		}

		$new_data = array();

		// Basic Authentication
		$auth = $this->get_api_auth();

		$response = wp_remote_get( $request_url, $auth );

		$options = get_option( 'uamswp_proclass_settings' );
		if(isset($options['uamswp_proclass_authentication_acct']) && !empty($options['uamswp_proclass_authentication_acct'])){
			$authacct = sanitize_text_field($options['uamswp_proclass_authentication_acct']);
		} else {
			// Set Default Value, if desired
			$authacct = 'UAMS';
		}

		if ( ! is_wp_error( $response ) && 404 !== wp_remote_retrieve_response_code( $response ) ) {
			$data = wp_remote_retrieve_body( $response );
			$data = json_decode( $data );

			if ( null === $data ) {
				$data = array();
			}

			$new_data = $this->process_remote_posts( $data, $atts );
		}

		ob_start();
		// By default, we output a JSON object that can then be used by a script.
		if ( 'headlines' === $atts['output'] ) {
			?>
			<div class="uamswp-proclass-calendar-wrapper">
				<ul class="uamswp-proclass-calendar-list">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?><li class="uamswp-proclass-calendar-item"><a href="https://reg126.imperisoft.com/<?php echo $authacct; ?>/ProgramDetail/<?php echo esc_html( $content->ID ); ?>/Registration.aspx"><?php echo esc_html( $content->title ); ?></a><br/><span class="content-item-date"><?php echo esc_html( date( $atts['date_format'], strtotime( $content->startdate ) ) ); ?></span></li><?php
					}
					?>
				</ul>
			</div>
			<?php
		} elseif ( 'excerpts' === $atts['output'] ) {
			?>
			<div class="uamswp-proclass-calendar-wrapper">
				<ul class="uamswp-proclass-calendar-excerpt">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?>
						<li class="uamswp-proclass-calendar-item">
							<span class="content-item-thumbnail"><?php if ( $content->image ) : ?><img src="<?php echo esc_url( $content->image ); ?>"><?php endif; ?></span>
							<span class="content-item-title"><a href="https://reg126.imperisoft.com/<?php echo $authacct; ?>/ProgramDetail/<?php echo esc_html( $content->ID ); ?>/Registration.aspx"><?php echo esc_html( $content->title ); ?></a></span>
							<span class="content-item-metainfo">
								<span class="content-item-date"><?php echo esc_html( date( "D", strtotime( $content->startdate ) ) ); ?> <?php echo esc_html( date( $atts['date_format'], strtotime( $content->startdate ) ) ); ?></span>
								<span class="content-item-level">[<?php echo esc_html( $content->level ); ?>]</span>
							</span>
							<span class="content-item-excerpt"><?php echo wp_kses_post( $content->content ); ?> <a class="content-item-read-story" href="https://reg126.imperisoft.com/<?php echo $authacct; ?>/ProgramDetail/<?php echo esc_html( $content->ID ); ?>/Registration.aspx">Learn More</a></span>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		} elseif ( 'minical' === $atts['output'] ) {
			?>
			<div class="uamswp-proclass-calendar-wrapper">
				<ul class="uamswp-proclass-calendar">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?>
						<li class="uamswp-proclass-calendar-mini">
							<span class="mini-calendar">
								<div class="day"><?php echo esc_html( date( "D", strtotime( $content->startdate ) ) ); ?></div>
								<div class="date"><?php echo esc_html( date( "M j", strtotime( $content->startdate ) ) ); ?></div>
							</span>
							<span class="meta-info">
								<h4 class="content-item-title"><a href="https://reg126.imperisoft.com/<?php echo $authacct; ?>/ProgramDetail/<?php echo esc_html( $content->ID ); ?>/Registration.aspx"><?php echo esc_html( $content->title ); ?></a></h4><span class="content-item-date"><?php echo esc_html( date( "g:i a", strtotime( $content->starttime ) ) ); ?></span> &nbsp; <span class="content-item-level">[Level: <?php echo esc_html( $content->level ); ?>]</span>
							</span>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		}
		$content = ob_get_contents();
		ob_end_clean();

		// Store the built content in cache for repeated use.
		$this->set_content_cache( $atts, 'proclass_calendar', $content );

		$content = apply_filters( 'uamswp_proclass_syndication_calendar', $content, $atts );

		return $content;
	}

	/**
	 * Process REST API results received remotely through `wp_remote_get()`
	 *
	 * @since 0.9.0
	 *
	 * @param object $data List of post data.
	 * @param array  $atts Attributes passed with the original shortcode.
	 *
	 * @return array Array of objects representing individual posts.
	 */
	public function process_remote_posts( $data, $atts ) {
		if ( empty( $data ) ) {
			return array();
		}

		$new_data = array();

		foreach ( $data as $post ) {
			$subset = new StdClass();
			$subset->ID = $post->ProgramDetailId;
			$subset->startdate = $post->StartDate; // In time zone of requested site
			$subset->enddate = $post->EndDate; // In time zone of requested site
			$subset->starttime = $post->StartTime; // In time zone of requested site
			$subset->endtime = $post->EndTime; // In time zone of requested site
			//$subset->link = $post->link;
			$subset->title   = $post->Description;
			$subset->shortdesc = $post->ShortDescription;
			$subset->content = $post->OnlineRegistrationDescription;
			$subset->status = $post->StatusDescription;
			$subset->level = $post->Level;
			$subset->fee = $post->TuitionFee;
			$subset->image = $post->ImageUrl;

			// We've always provided an empty value for terms. @todo Implement terms. :)
			$subset->terms = array();

			/**
			 * Filter the data stored for an individual result after defaults have been built.
			 *
			 * @since 0.7.10
			 *
			 * @param object $subset Data attached to this result.
			 * @param object $post   Data for an individual post retrieved via `wp-json/posts` from a remote host.
			 * @param array  $atts   Attributes originally passed to the `proclass_calendar` shortcode.
			 */
			$subset = apply_filters( 'uams_proclass_calendar_host_data', $subset, $post, $atts );

			if ( $post->date ) {
				$subset_key = strtotime( $post->date );
			} else {
				$subset_key = time();
			}

			while ( array_key_exists( $subset_key, $new_data ) ) {
				$subset_key++;
			}
			$new_data[ $subset_key ] = $subset;
		} // End foreach().

		return $new_data;
	}

}
