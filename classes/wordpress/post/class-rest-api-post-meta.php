<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rest_Api_Post_Meta' ) ) :

	class Rest_Api_Post_Meta {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Rest_Api_Post_Meta ) ) {
				self::$instance = new Rest_Api_Post_Meta;
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_wordpress_routes' ), 10 );
		}

		/**
		 * definition new Api routes
		 *
		 * @param server The server object.
		 */
		public function wpr_rest_api_wordpress_routes( $server ) {
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-post-meta',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_post_meta_callback' ),
				)
			);
		}

		/**
		 * It gets the post meta data from the post id and meta key.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_post_meta_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$post_id  = preg_replace( '/[^0-9\-]/i', '', $params['post_id'] );
			$meta_key = preg_replace( '/[^a-zA-Z0-9\-\_]/i', '', $params['meta_key'] );

			$meta = get_post_meta( $post_id, $meta_key );

			wp_send_json(
				array(
					'status'  => ( ! empty( $meta ) ? 'success' : 'error' ),
					'message' => ( ! empty( $meta ) ? $meta : "there isn't meta" ),
				)
			);
		}
	}

endif;

Rest_Api_Post_Meta::instance();
