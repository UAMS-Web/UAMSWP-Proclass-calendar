<?php
/*
Plugin Name: UAMSWP Proclass Calendar
Plugin URI: -
Description: Proclass Calendar Syndication plugin for uamscaregiving.org
Author: uams, Todd McKee, MEd
Author URI: http://www.uams.edu/
Version: 2.0
*/

namespace UAMS\ProclassCalendar;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

include_once __DIR__ . '/includes/class-uams-proclass-admin.php';

add_action( 'plugins_loaded', 'UAMS\ProclassCalendar\bootstrap' );
/**
 * Loads the WSUWP Content Syndicate base.
 *
 * @since 1.0.0
 */
function bootstrap() {
	include_once __DIR__ . '/includes/class-uams-proclass-shortcode-base.php';

	add_action( 'init', 'UAMS\ProclassCalendar\activate_shortcodes' );
}

/**
 * Activates the shortcodes built in with WSUWP Content Syndicate.
 *
 * @since 1.0.0
 */
function activate_shortcodes() {
	include_once( dirname( __FILE__ ) . '/includes/class-uams-proclass-shortcode-calendar.php' );

	// Add the [proclass_calendar] shortcode to pull standard post content.
	new \UAMS_Proclass_Shortcode_Calendar();

	do_action( 'uamswp_proclass_calendar_shortcodes' );
}