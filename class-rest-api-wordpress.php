<?php
/**
 * Plugin Name: REST API WP/Woo
 * Plugin URI: #
 * Description:
 * Version: 0.0.5
 * Author: Andrei Leca
 * Author URI:
 * Text Domain: WordPress
 * License: MIT
 */

namespace Hmd\Rest_Api_WordPress;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rest_Api_Wordpress' ) ) :

	class Rest_Api_WordPress {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Rest_Api_WordPress ) ) {
				self::$instance = new Rest_Api_WordPress;
				self::$instance->constants();
				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 * Constants
		 */
		public function constants() {

			$upload = wp_get_upload_dir();

			// Plugin version
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_VERSION' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_VERSION', '0.0.5' );
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

			// Upload folder for contents
			if ( ! defined( 'WPR_REST_API_WORDPRESS_PLUGIN_UPLOAD_DIR_PATH' ) ) {
				define( 'WPR_REST_API_WORDPRESS_PLUGIN_UPLOAD_DIR_PATH', trailingslashit( $upload['basedir'] . '/rest_api_imgs' ) );
			}

			// Upload folder url for contents
			if ( ! defined( 'WPR_REST_API_WORDPRESS_PLUGIN_UPLOAD_DIR_URL' ) ) {
				define( 'WPR_REST_API_WORDPRESS_PLUGIN_UPLOAD_DIR_URL', trailingslashit( $upload['baseurl'] . '/rest_api_imgs' ) );
			}

			// Plugin token registration new user routes
			// NOTE: PLEASE CHANGE THIS TOKEN 'MySuperSecretToken'
			if ( ! defined( 'REST_API_WORDPRESS_PLUGIN_TOKEN' ) ) {
				define( 'REST_API_WORDPRESS_PLUGIN_TOKEN', 'MySuperSecretToken' );
			}
		}

		/**
		 * It includes the files that are required for the plugin to work.
		 */
		public function includes() {
			if ( file_exists( REST_API_WORDPRESS_PLUGIN_DIR_PATH . 'vendor/autoload.php' ) ) {
				require_once( REST_API_WORDPRESS_PLUGIN_DIR_PATH . 'vendor/autoload.php' );
			}

			require_once( REST_API_WORDPRESS_PLUGIN_DIR_PATH . 'inc/class-update-cart.php' );

			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/class-rest-api-woocommerce.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/class-wpr-wc-api-order.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'wordpress/class-rest-api-wordpresswp.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'auth/class-user-role-api.php' );

			\User_Role_Api::instance();
			\Rest_Api_WooCommerce::instance();
			\Rest_Api_WordpressWP::instance();
			\WPR_WC_API_Order::instance();
		}
	}

endif;

add_action( 'plugins_loaded', array( Rest_Api_WordPress::class, 'instance' ), 100 );
