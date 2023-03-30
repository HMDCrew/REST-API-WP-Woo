<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Stripe\Stripe;
use \Stripe\PaymentIntent;

if ( ! class_exists( 'Rest_Api_WooCommerce' ) ) :

	class Rest_Api_WooCommerce {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Rest_Api_WooCommerce ) ) {
				self::$instance = new Rest_Api_WooCommerce;
				self::$instance->includes();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		public function includes() {
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/class-wpr-wc-api-store.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/class-wpr-wc-api-cart.php' );
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/class-wpr-wc-api-checkout.php' );

			\WPR_WC_API_Store::instance();
			\WPR_WC_API_Cart::instance();
			\WPR_WC_API_Checkout::instance();
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
				'/wpr-test-route',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_woocommerce_test_route_callback' ),
				)
			);

			// Payment
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-stripe-payment',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'payment_api_request' ),
				)
			);
		}

		public function wpr_get_woocommerce_test_route_callback( \WP_REST_Request $request ) {

			$attrs = $request->get_attributes();

			if ( isset( $attrs['login_user_id'] ) && intval( $attrs['login_user_id'] ) > 0 ) {

				$user_id = intval( $attrs['login_user_id'] );
				// WC()->customer = new WC_Customer( $user_id, true );

				// $user_cart = get_user_meta( $user_id, '_woocommerce_persistent_cart_1', true );
				// $wc_cart   = WC()->cart->get_cart();

				// $cart_for_meta = array();
				// foreach ( $wc_cart as $key => $cart_product ) {
				// 	$cart_for_meta[ $key ] = $this->clean_for_meta_cart_product( $cart_product );
				// }

				// update_user_meta( $user_id, '_woocommerce_persistent_cart_1', array( 'cart' => $cart_for_meta ) );

				// $full_user_meta['cart'] = WC()->cart->get_cart();
				// var_dump( $wc_cart );
				// $product_stock = $product->get_stock_quantity();
			}

			exit();
		}


		/**
		 * ?
		 * Preg_replace missing
		 *
		 * @param \WP_REST_Request $request
		 * @return void
		 */
		public function payment_api_request( \WP_REST_Request $request ) {

			$params = $request->get_params();

			if ( isset( $params['paymentMethodId'] ) ) {

				Stripe::setApiKey( 'sk_test_51LnfxgFr4Sza9qfAyAnnwbzTzHdQKCfcGowRZjuGloRtjLYL0HvhUkqTWaowbgDZEUTotOqwPtAvskkBN45NBGiR007v7Qv9UF' );

				$intent = PaymentIntent::create(
					array(
						'payment_method'      => $params['paymentMethodId'],
						'amount'              => 3099,
						'currency'            => 'usd',
						'confirmation_method' => 'manual',
						'confirm'             => true,
					)
				);

				wp_send_json(
					array(
						'status'  => ( ! empty( $intent ) ? 'success' : 'error' ),
						'message' => ( ! empty( $intent ) ? $intent : 'Order has been not created' ),
					)
				);
			}
		}
	}

endif;

Rest_Api_WooCommerce::instance();
