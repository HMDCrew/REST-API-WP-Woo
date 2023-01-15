<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rest_Api_WordpressWP' ) ) :

	class Rest_Api_WordpressWP {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Rest_Api_WordpressWP ) ) {
				self::$instance = new Rest_Api_WordpressWP;
				self::$instance->includes();
			}

			return self::$instance;
		}

		public function includes() {
			require_once( REST_API_WORDPRESS_PLUGIN_DIR_PATH . 'helper.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'wordpress/post/class-rest-api-post-type.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'wordpress/post/class-rest-api-post-meta.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'wordpress/taxonomy/class-rest-api-taxonomy.php' );

			\Rest_Api_Post_Type::instance();
			\Rest_Api_Post_Meta::instance();
			\Rest_Api_Taxonomy::instance();
		}
	}

endif;

Rest_Api_WordpressWP::instance();
