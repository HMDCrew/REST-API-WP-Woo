<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPR_WC_API_Order' ) ) :
	class WPR_WC_API_Order extends Update_Cart {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPR_WC_API_Order ) ) {
				self::$instance = new WPR_WC_API_Order;
				self::$instance->hooks();
			}

			return self::$instance;
		}


		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_order_routes' ), 10 );
		}

		public function wpr_rest_api_order_routes( $server ) {

			$server->register_route(
				'rest-api-wordpress',
				'/wpr-create-order',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'create_new_order_post' ),
				)
			);
		}

		/**
		 * It adds products to an order
		 *
		 * @param array cart The cart array from the order.
		 * @param WC_Order order The order object
		 *
		 * @return WC_Order order object.
		 */
		private function sync_new_order_cart( array $cart, WC_Order $order ) {

			foreach ( $cart['items'] as $product ) {

				$wc_product = wc_get_product( $product['product_id'] );

				$order->add_product(
					$wc_product,
					$product['quantity'],
					$product
				);
			}

			return $order;
		}

		/**
		 * It creates a new order in WooCommerce
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function create_new_order_post( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$address        = array();
			$user_id        = wpr_auth_api_user_id( $request );
			$missing_fields = validate_chackout_fields( $params['form_fields'] );
			$response       = array(
				'status'  => 'error',
				'message' => 'error get_current_user_id',
				'fields'  => array(),
			);

			if ( empty( $missing_fields ) && $user_id ) {

				foreach ( $params['form_fields'] as $field ) {
					$name             = str_replace( 'billing_', '', preg_replace( '/[^a-zA-Z0-9\_\-\s]/i', '', $field['name'] ) );
					$address[ $name ] = preg_replace( '/[^a-zA-Z0-9\.\@\_\-\s]/i', '', $field['value'] );
				}

				$order = wc_create_order();
				update_post_meta( $order->get_id(), '_customer_user', $user_id );

				if ( ! is_a( $order, 'WP_Error' ) ) {

					$order = $this->sync_new_order_cart(
						$this->wpr_get_cart( $user_id ),
						$order
					);
				}

				$order->set_address( $address, 'billing' );
				$order->calculate_totals();
				$order->save();

				$response['status']  = ( ! empty( $order ) ? 'success' : 'error' );
				$response['message'] = ( ! empty( $order ) ? $order->get_data() : 'Order has been not created' );
			}

			wp_send_json( $response );
		}
	}
endif;

\WPR_WC_API_Order::instance();
