<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rest_Api_Post_Type' ) ) :

	class Rest_Api_Post_Type {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Rest_Api_Post_Type ) ) {
				self::$instance = new Rest_Api_Post_Type;
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
				'/wpr-get-posts',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_posts_callback' ),
				)
			);
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-post',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_post_callback' ),
				)
			);
		}

		/**
		 * It gets the post meta values for the given post id and meta keys.
		 *
		 * @param int post_id The ID of the post you want to get the meta values from.
		 * @param array meta_keys An array of meta keys to get the values for.
		 *
		 * @return array of meta values.
		 */
		private function wpr_get_post_metas( int $post_id, array $meta_keys ) {

			$meta_values = array();
			foreach ( $meta_keys as $key ) {
				$meta_values[ $key ] = get_post_meta( $post_id, $key, true );
			}

			return $meta_values;
		}

		/**
		 * It removes different properties of the post object that we don't need
		 *
		 * @param WP_Post The post object.
		 *
		 * @return WP_Post post object is being returned.
		 */
		private function clean_unused_values_post( \WP_Post $post ) {
			unset( $post->post_author );
			unset( $post->post_date );
			unset( $post->post_date_gmt );
			unset( $post->post_content );
			unset( $post->post_status );
			unset( $post->post_password );
			unset( $post->to_ping );
			unset( $post->pinged );
			unset( $post->post_modified );
			unset( $post->post_modified_gmt );
			unset( $post->post_content_filtered );
			unset( $post->menu_order );
			unset( $post->post_mime_type );
			unset( $post->comment_count );
			unset( $post->filter );
			unset( $post->comment_status );
			unset( $post->ping_status );

			return $post;
		}

		/**
		 * It gets the posts from the database and returns them in a JSON format
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_posts_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$post_type     = ( ! empty( $params['post_type'] ) ? preg_replace( '/[^a-zA-Z0-9\_\-]/', '', $params['post_type'] ) : 'post' );
			$numberposts   = ( ! empty( $params['numberposts'] ) ? preg_replace( '/[^0-9\-]/i', '', $params['numberposts'] ) : 5 );
			$include_metas = ( ! empty( $params['include_metas'] ) ? preg_replace( '/[^a-zA-Z0-9\,\_\-\[\]]/', '', $params['include_metas'] ) : '' );
			$page          = ( ! empty( $params['page'] ) ? preg_replace( '/[^0-9]/i', '', $params['page'] ) : 0 );
			$search        = ( ! empty( $params['search'] ) ? preg_replace( '/[^0-9a-zA-Z\{\}\(\)\"\[\]\/\s\:\,\.\_\-]/i', '', $params['search'] ) : '' );

			$args = array(
				'post_type'   => $post_type,
				'numberposts' => $numberposts,
				'paged'       => $page,
			);

			if ( ! empty( $search ) ) {
				$args['s'] = $search;
			}

			$postslist = get_posts( $args );

			$include_metas = explode( ',', str_replace( array( '[', ']' ), '', $include_metas ) );

			foreach ( $postslist as $key => $value ) {

				$postslist[ $key ] = (array) $this->clean_unused_values_post( $postslist[ $key ] );

				$postslist[ $key ]['include_metas'] = $this->wpr_get_post_metas( $postslist[ $key ]['ID'], $include_metas );
				$postslist[ $key ]['guid']          = get_permalink( $postslist[ $key ]['ID'] );
				$postslist[ $key ]['image']         = get_the_post_thumbnail_url( $postslist[ $key ]['ID'], 'full' );
			}

			wp_send_json(
				array(
					'status'  => ( ! empty( $postslist ) ? 'success' : 'error' ),
					'message' => ( ! empty( $postslist ) ? $postslist : "there isn't posts" ),
				)
			);
			exit();
		}

		/**
		 * It gets a post by ID and returns it as JSON.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_post_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$post_id = ( ! empty( $params['post_id'] ) ? preg_replace( '/[^0-9\-]/i', '', $params['post_id'] ) : 5 );

			$post = $this->clean_unused_values_post(
				get_post( $post_id )
			);

			wp_send_json(
				array(
					'status'  => ( ! empty( $post ) ? 'success' : 'error' ),
					'message' => ( ! empty( $post ) ? $post : 'post not found' ),
				)
			);

			exit();
		}
	}

endif;

Rest_Api_Post_Type::instance();
