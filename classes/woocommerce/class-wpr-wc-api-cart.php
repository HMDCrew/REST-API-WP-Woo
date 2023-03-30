<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPR_WC_API_Cart' ) ) :

	class WPR_WC_API_Cart extends Update_Cart {


		private static $instance;


		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPR_WC_API_Cart ) ) {
				self::$instance = new WPR_WC_API_Cart;
				self::$instance->hooks();
			}

			return self::$instance;
		}


		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_cart_routes' ), 10 );
		}


		/**
		 * It registers the routes for the cart endpoints
		 *
		 * @param server The server object.
		 */
		public function wpr_rest_api_cart_routes( $server ) {

			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-cart',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_get_cart_callback' ),
				)
			);

			$server->register_route(
				'rest-api-wordpress',
				'/wpr-update-cart',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_update_cart_callback' ),
				)
			);

			$server->register_route(
				'rest-api-wordpress',
				'/wpr-add-to-cart',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_add_to_cart_callback' ),
				)
			);

			$server->register_route(
				'rest-api-wordpress',
				'/wpr-remove-cart-product',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_remove_cart_product_callback' ),
				)
			);
		}


		/**
		 * It gets the cart of the user.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_cart_callback( \WP_REST_Request $request ) {

			wpr_hide_php_errors();

			$response = array(
				'status'  => 'error',
				'message' => 'error get_current_user_id',
			);

			$user_id = wpr_auth_api_user_id( $request );

			if ( $user_id ) {
				$cart = $this->wpr_get_cart( $user_id );

				$response['status']  = ( $cart ? 'success' : 'error' );
				$response['message'] = ( $cart ? $cart : 'error on wpr_get_cart' );
			}

			wp_send_json( $response );
			exit();
		}


		/**
		 * It updates the cart.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_update_cart_callback( \WP_REST_Request $request ) {

			wpr_hide_php_errors();

			$params = $request->get_params();

			$response = array(
				'status'  => 'error',
				'message' => 'error get_current_user_id',
			);

			$user_id = wpr_auth_api_user_id( $request );

			if ( $user_id ) {

				$update_status = $this->update_cart_from_api( $user_id, $params['cart'] );

				$response['status']  = ( ! empty( $update_status['items'] ) ? 'success' : 'error' );
				$response['message'] = ( ! empty( $update_status['items'] ) ? $update_status : 'cart is empty' );
			}

			wp_send_json( $response );
			exit();
		}


		/**
		 * It add product to the cart.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_add_to_cart_callback( \WP_REST_Request $request ) {

			wpr_hide_php_errors();

			$params = $request->get_params();

			$product_id   = ( ! empty( $params['product_id'] ) ? preg_replace( '/[^0-9]/i', '', $params['product_id'] ) : 0 );
			$qty          = ( ! empty( $params['qty'] ) ? preg_replace( '/[^0-9]/i', '', $params['qty'] ) : 0 );
			$variation_id = ( ! empty( $params['variation_id'] ) ? preg_replace( '/[^0-9a-zA-Z\s\.\,\-\_\!\?\(\)\[\]]/i', '', $params['variation_id'] ) : 0 );

			$response = array(
				'status'  => 'error',
				'message' => 'product not added tu cart contact admin function generate error get_current_user_id',
			);

			$user_id = wpr_auth_api_user_id( $request );

			if ( $user_id ) {

				$user_cart_product = $this->wpr_wc_add_to_cart(
					$user_id,
					$product_id,
					$qty,
					$variation_id
				);

				$response['status']  = ( $user_cart_product ? 'success' : 'error' );
				$response['message'] = ( $user_cart_product ? $user_cart_product : 'product not added tu cart' );
			}

			wp_send_json( $response );
			exit();
		}


		/**
		 * It removes a product from the cart
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_remove_cart_product_callback( \WP_REST_Request $request ) {
			wpr_hide_php_errors();

			$params = $request->get_params();

			$cart_prod_key = ( ! empty( $params['key'] ) ? preg_replace( '/[^a-zA-Z0-9\.\@\_\-\s]/i', '', $params['key'] ) : '' );

			$response = array(
				'status'  => 'error',
				'message' => 'product are not removed from cart',
			);

			$user_id = wpr_auth_api_user_id( $request );

			if ( $user_id ) {

				$user_cart_product = $this->wpr_wc_remove_item_cart( $user_id, $cart_prod_key );

				$response['status']  = ( $user_cart_product ? 'success' : 'error' );
				$response['message'] = ( $user_cart_product ? $user_cart_product : 'product are not removed from cart has user id' . $user_id );
			}

			wp_send_json( $response );
		}
	}

endif;

WPR_WC_API_Cart::instance();
