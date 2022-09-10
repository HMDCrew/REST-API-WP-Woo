<?php
/**
 * Plugin Name: REST API WP/Woo
 * Plugin URI: #
 * Description:
 * Version: 0.0.1
 * Author: Andrei Leca
 * Author URI:
 * Text Domain: WordPress
 * License: GPL-2.0+
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */

namespace RestApiWordpress;

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
		}

		public function includes() {
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/class-restapiwoocommerce.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'wordpress/class-restapiwordpresswp.php' );

			\RestApiWooCommerce::instance();
			\RestApiWordpressWP::instance();
		}
	}

endif;

RestApiWordpress::instance();
