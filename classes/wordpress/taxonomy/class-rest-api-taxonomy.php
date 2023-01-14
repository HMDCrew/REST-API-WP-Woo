<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Rest_Api_Taxonomy' ) ) :

	class Rest_Api_Taxonomy {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Rest_Api_Taxonomy ) ) {
				self::$instance = new Rest_Api_Taxonomy;
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
				'/wpr-get-taxonomy',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_get_taxonomy_callback' ),
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

Rest_Api_Taxonomy::instance();
