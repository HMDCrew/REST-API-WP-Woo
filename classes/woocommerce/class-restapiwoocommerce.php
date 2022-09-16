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
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_get_products_callback' ),
				)
			);
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-product',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_get_product_callback' ),
				)
			);
		}

		public function wpr_get_products_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$numberposts = ( ! empty( $params['numberposts'] ) ? preg_replace( '/[^0-9\-]/i', '', $params['numberposts'] ) : 5 );
			$categorys   = ( ! empty( $params['category'] ) ? preg_replace( '/[^0-9a-zA-Z\{\}\(\)\"\[\]\/\s\:\,\.\_\-]/i', '', $params['category'] ) : false );
			$page        = ( ! empty( $params['page'] ) ? preg_replace( '/[^0-9]/i', '', $params['page'] ) : 0 );

			$args = array(
				'status' => 'publish',
				'limit'  => $numberposts,
				'page'   => $page,
			);

			if ( ! empty( $categorys ) ) {

				$categorys = json_decode( $categorys, true );

				if ( isset( $categorys['slug'] ) ) {
					$args['category'][] = $categorys['slug'];
				} else {
					foreach ( $categorys as $tax ) {
						$args['category'][] = $tax['slug'];
					}
				}
			}

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
				unset( $data['attributes'] );

				$image             = wp_get_attachment_image_url( $data['image_id'], 'medium' );
				$data['image_uri'] = ( $image ? $image : wc_placeholder_img_src( 'medium' ) );
				$data['symbol']    = html_entity_decode( get_woocommerce_currency_symbol() );

				$prod_garbage[] = $data;
			}

			wp_send_json(
				array(
					'status'  => ( ! empty( $prod_garbage ) ? 'success' : 'error' ),
					'message' => ( ! empty( $prod_garbage ) ? $prod_garbage : "there isn't products" ),
				)
			);
		}

		public function wpr_get_product_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$product_id = ( ! empty( $params['product_id'] ) ? preg_replace( '/[^0-9]/i', '', $params['product_id'] ) : 0 );

			$product      = wc_get_product( $product_id );
			$product_data = (array) $product->get_data();

			foreach ( $product_data['gallery_image_ids'] as $key => $id_img ) {
				$image = wp_get_attachment_image_url( $id_img, 'medium' );

				$product_data['gallery_image_ids'][ $key ] = ( $image ? $image : wc_placeholder_img_src( 'medium' ) );
			}

			$product_data['symbol'] = html_entity_decode( get_woocommerce_currency_symbol() );

			wp_send_json(
				array(
					'status'  => ( ! empty( $product_data ) ? 'success' : 'error' ),
					'message' => ( ! empty( $product_data ) ? $product_data : 'Product not exist' ),
				)
			);
		}
	}

endif;

RestApiWooCommerce::instance();
