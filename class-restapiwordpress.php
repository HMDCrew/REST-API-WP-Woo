<?php
/**
 * Plugin Name: REST API WP/Woo
 * Plugin URI: #
 * Description:
 * Version: 0.0.2
 * Author: Andrei Leca
 * Author URI:
 * Text Domain: WordPress
 * License: GPL-2.0+
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */

namespace Hmd\RestApiWordpress;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'RestApiWordpress' ) ) :

	class RestApiWordpress {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RestApiWordpress ) ) {
				self::$instance = new RestApiWordpress;
				self::$instance->constants();
				self::$instance->hooks();
				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 * Constants
		 */
		public function constants() {
			// Plugin version
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_VERSION' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_VERSION', '0.0.1' );
			}

			// Plugin file
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_FILE' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_FILE', __FILE__ );
			}

			// Plugin basename
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_BASENAME' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_BASENAME', plugin_basename( REST_API_WORDPRESS_PLUGIN_FILE ) );
			}

			// Plugin directory path
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_DIR_PATH' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_DIR_PATH', trailingslashit( plugin_dir_path( REST_API_WORDPRESS_PLUGIN_FILE ) ) );
			}

			// Plugin directory classes
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_CLASSES' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_CLASSES', trailingslashit( REST_API_WORDPRESS_PLUGIN_DIR_PATH . 'classes' ) );
			}

			// Plugin directory URL
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_DIR_URL' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_DIR_URL', trailingslashit( plugin_dir_url( REST_API_WORDPRESS_PLUGIN_FILE ) ) );
			}

			// Plugin token registration new user routes
			// NOTE: PLEASE CHANGE THIS TOKEN 'MySuperSecretToken'
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_TOKEN' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_TOKEN', 'MySuperSecretToken' );
			}
		}

		/**
		 * It disables the nonce check for the WooCommerce REST API.
		 */
		public function hooks() {
			// add_action(
			// 	'plugins_loaded',
			// 	function() {
			// 		if ( is_user_logged_in() ) {
			// 			add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
			// 		}
			// 	}
			// );
		}

		/**
		 * It includes the files that are required for the plugin to work.
		 */
		public function includes() {
			require_once( REST_API_WORDPRESS_PLUGIN_DIR_PATH . 'vendor/autoload.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/class-restapiwoocommerce.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'wordpress/class-restapiwordpresswp.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'auth/class-registrationapiwordpresswp.php' );

			\RestApiWooCommerce::instance();
			\RestApiWordpressWP::instance();
			\RegistrationApiWordpressWP::instance();
		}
	}

endif;

RestApiWordpress::instance();
