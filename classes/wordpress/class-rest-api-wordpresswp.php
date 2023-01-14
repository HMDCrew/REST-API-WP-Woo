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

		public function includes() {
			require_once( REST_API_WORDPRESS_PLUGIN_DIR_PATH . 'helper.php' );
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
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-post-meta',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_post_meta_callback' ),
				)
			);
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-taxonomy',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_get_taxonomy_callback' ),
				)
			);
		}

		/**
		 * It gets the post meta values for the given post id and meta keys.
		 *
		 * @param int post_id The ID of the post you want to get the meta values from.
		 * @param array meta_keys An array of meta keys to get the values for.
		 *
		 * @return An array of meta values.
		 */
		public function wpr_get_post_metas( int $post_id, array $meta_keys ) {

			$meta_values = array();
			foreach ( $meta_keys as $key ) {
				$meta_values[ $key ] = get_post_meta( $post_id, $key, true );
			}

			return $meta_values;
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
				unset( $postslist[ $key ]->post_author );
				unset( $postslist[ $key ]->post_date );
				unset( $postslist[ $key ]->post_date_gmt );
				unset( $postslist[ $key ]->post_content );
				unset( $postslist[ $key ]->post_status );
				unset( $postslist[ $key ]->post_password );
				unset( $postslist[ $key ]->to_ping );
				unset( $postslist[ $key ]->pinged );
				unset( $postslist[ $key ]->post_modified );
				unset( $postslist[ $key ]->post_modified_gmt );
				unset( $postslist[ $key ]->post_content_filtered );
				unset( $postslist[ $key ]->menu_order );
				unset( $postslist[ $key ]->post_mime_type );
				unset( $postslist[ $key ]->comment_count );
				unset( $postslist[ $key ]->filter );
				unset( $postslist[ $key ]->comment_status );
				unset( $postslist[ $key ]->ping_status );

				$postslist[ $key ]->include_metas = $this->wpr_get_post_metas( $postslist[ $key ]->ID, $include_metas );
				$postslist[ $key ]->guid          = get_permalink( $postslist[ $key ]->ID );
				$postslist[ $key ]->image         = get_the_post_thumbnail_url( $postslist[ $key ]->ID, 'full' );
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

			$post = get_post( $post_id );

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

			wp_send_json(
				array(
					'status'  => ( ! empty( $post ) ? 'success' : 'error' ),
					'message' => ( ! empty( $post ) ? $post : 'post not found' ),
				)
			);

			exit();
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

		/**
		 * It returns the taxonomy of the post.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_taxonomy_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$taxonomy   = ( ! empty( $params['taxonomy'] ) ? preg_replace( '/[^a-zA-Z0-9\-\_]/i', '', $params['taxonomy'] ) : 'category' );
			$hide_empty = ( ! empty( $params['hide_empty'] ) ? preg_replace( '/[^0-1]/i', '', $params['hide_empty'] ) : '0' );

			$args = array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => ( '0' !== $hide_empty ? true : false ),
			);

			$terms = get_terms( $args );

			// Interpolation array
			$terms_mapped = array();
			foreach ( $terms as $key => $term ) {

				unset( $term->term_group );
				unset( $term->term_taxonomy_id );
				unset( $term->filter );
				unset( $term->taxonomy );

				$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );

				$terms_mapped[ $term->term_id ]          = (array) $term;
				$terms_mapped[ $term->term_id ]['image'] = wp_get_attachment_url( $thumbnail_id );
			}

			// sub_category mapping
			$terms_mapped = sub_category_mapping( $terms_mapped );

			wp_send_json(
				array(
					'status'  => ( ! empty( $terms_mapped ) ? 'success' : 'error' ),
					'message' => ( ! empty( $terms_mapped ) ? $terms_mapped : "there isn't taxonomys" ),
				)
			);
		}
	}

endif;

Rest_Api_WordpressWP::instance();
