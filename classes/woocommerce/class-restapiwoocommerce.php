<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'RestApiWooCommerce' ) ) :

	class RestApiWooCommerce {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RestApiWooCommerce ) ) {
				self::$instance = new RestApiWooCommerce;
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_woocommerce_routes' ), 10 );
		}

		public function wpr_rest_api_woocommerce_routes( $server ) {
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-products',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_products_callback' ),
				)
			);
		}

		public function wpr_get_products_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$numberposts = preg_replace( '/[^0-9\-]/i', '', $params['numberposts'] );

			$args = array(
				'limit' => ( ! empty( $numberposts ) ? $numberposts : 5 ),
			);

			$products = wc_get_products( $args );

			$prod_garbage = array();
			foreach ( $products as $product ) {
				$data = (array) $product->get_data();

				unset( $data['date_created'] );
				unset( $data['date_modified'] );
				unset( $data['description'] );
				unset( $data['reviews_allowed'] );
				unset( $data['post_password'] );
				unset( $data['rating_counts'] );
				unset( $data['review_count'] );
				unset( $data['virtual'] );
				unset( $data['date_on_sale_from'] );
				unset( $data['date_on_sale_to'] );
				unset( $data['tax_status'] );
				unset( $data['stock_quantity'] );
				unset( $data['manage_stock'] );
				unset( $data['weight'] );
				unset( $data['length'] );
				unset( $data['width'] );
				unset( $data['height'] );
				unset( $data['menu_order'] );
				unset( $data['meta_data'] );

				$prod_garbage[] = $data;
			}

			wp_send_json(
				array(
					'status'  => ( ! empty( $prod_garbage ) ? 'success' : 'error' ),
					'message' => ( ! empty( $prod_garbage ) ? $prod_garbage : "there isn't products" ),
				)
			);
		}
	}

endif;

RestApiWooCommerce::instance();
