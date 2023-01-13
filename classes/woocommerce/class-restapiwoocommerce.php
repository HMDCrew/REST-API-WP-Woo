<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Stripe\Stripe;
use \Stripe\PaymentIntent;

if ( ! class_exists( 'RestApiWooCommerce' ) ) :

	class RestApiWooCommerce {

		private static $instance;
		public $update_cart;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof RestApiWooCommerce ) ) {
				self::$instance = new RestApiWooCommerce;
				self::$instance->includes();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * It includes the files that are required for the plugin to work.
		 */
		public function includes() {
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/cart/class-updatecart.php' );

			$this->update_cart = new UpdateCart;
		}

		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_woocommerce_routes' ), 10 );
		}

		public function wpr_rest_api_woocommerce_routes( $server ) {

			// API
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-nonce',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_woocommerce_nonce_callback' ),
				)
			);

			// Store
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

			// Cart
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-update-cart',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'wpr_update_cart_callback' ),
				)
			);

			// Chackout
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-payment-gateway',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_payment_gateway_callback' ),
				)
			);
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-chackout-fields',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_chackout_fields' ),
				)
			);

			// Order
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-create-order',
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'create_new_order_post' ),
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

		/**
		 * It creates a nonce and returns it as a JSON response
		 */
		public function wpr_get_woocommerce_nonce_callback() {

			$nonce = wp_create_nonce( 'wc_store_api' );

			wp_send_json(
				array(
					'status'  => ( ! empty( $nonce ) ? 'success' : 'error' ),
					'message' => ( ! empty( $nonce ) ? $nonce : 'error generate nonce' ),
				)
			);
		}

		/**
		 * It gets the products from the database and returns them in a JSON format
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_products_callback( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$numberposts = ( ! empty( $params['numberposts'] ) ? preg_replace( '/[^0-9\-]/i', '', $params['numberposts'] ) : 5 );
			$categorys   = ( ! empty( $params['category'] ) ? preg_replace( '/[^0-9a-zA-Z\{\}\(\)\"\[\]\/\s\:\,\.\_\-]/i', '', $params['category'] ) : '' );
			$page        = ( ! empty( $params['page'] ) ? preg_replace( '/[^0-9]/i', '', $params['page'] ) : 0 );
			$search      = ( ! empty( $params['search'] ) ? preg_replace( '/[^0-9a-zA-Z\{\}\(\)\"\[\]\/\s\:\,\.\_\-]/i', '', $params['search'] ) : '' );

			$args = array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => $numberposts,
				'fields'      => 'ids',
				'paged'       => $page,
				'meta_query'  => array(
					'relation' => 'AND',
					array(
						'key'     => '_sku',
						'value'   => '',
						'compare' => 'NOT IN',
					),
				),
			);

			if ( ! empty( $search ) ) {
				$args['s'] = $search;
			}

			if ( ! empty( $categorys ) ) {

				$categorys = json_decode( $categorys, true );
				$terms     = array();

				if ( isset( $categorys['slug'] ) ) {
					$terms = $categorys['slug'];
				} else {
					foreach ( $categorys as $tax ) {
						$terms[] = $tax['slug'];
					}
				}

				$args['tax_query'][] = array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => $terms,
				);
			}

			$products_ids = get_posts( $args );
			$prod_garbage = array();
			foreach ( $products_ids as $prod_id ) {
				$data = (array) wc_get_product( $prod_id )->get_data();

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

		/**
		 * It takes a product ID, gets the product data, and returns it as a JSON object
		 *
		 * @param \WP_REST_Request request The request object.
		 */
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

		/**
		 * It updates the cart.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_update_cart_callback( \WP_REST_Request $request ) {

			$params        = $request->get_params();
			$update_status = false;

			foreach ( $params['cart'] as $item ) {

				$update_item_qty = $this->update_cart->update_item_qty( $item );

				if ( ! $update_status ) {
					$update_status = $update_item_qty;
				}
			}

			wp_send_json(
				array(
					'status'  => ( $update_status ? 'success' : 'error' ),
					'message' => ( $update_status ? 'cart updated' : 'haven\'t updates for cart' ),
				)
			);
		}

		/**
		 * ?
		 * Need restriction for payment gateway
		 *
		 * @return void
		 */
		public function wpr_payment_gateway_callback() {
			$gateways = WC()->payment_gateways->get_available_payment_gateways();
			wp_send_json(
				array(
					'status'  => ( ! empty( $gateways ) ? 'success' : 'error' ),
					'message' => ( ! empty( $gateways ) ? $gateways : 'haven\'t gateway for payments' ),
				)
			);
		}

		/**
		 * It gets the checkout fields from WooCommerce and sends them back to the browser as a JSON object
		 */
		public function wpr_get_chackout_fields() {

			$chackout_fields = WC()->checkout()->checkout_fields;

			wp_send_json(
				array(
					'status'  => ( ! empty( $chackout_fields ) ? 'success' : 'error' ),
					'message' => ( ! empty( $chackout_fields ) ? $chackout_fields : 'Woocommerce problem with chackout fields' ),
				)
			);
		}

		/**
		 * ?
		 * add preg_replace => for security risck in $_POST array
		 *
		 * @return void
		 */
		public function create_new_order_post( \WP_REST_Request $request ) {

			$params = $request->get_params();

			$address        = array();
			$missing_fields = validate_chackout_fields( $params['form_fields'] );
			if ( empty( $missing_fields ) ) {
				foreach ( $params['form_fields'] as $field ) {
					$name             = str_replace( 'billing_', '', preg_replace( '/[^a-zA-Z0-9\_\-\s]/i', '', $field['name'] ) );
					$address[ $name ] = preg_replace( '/[^a-zA-Z0-9\.\@\_\-\s]/i', '', $field['value'] );
				}

				$order  = wc_create_order();
				$userid = get_current_user_id();
				if ( $userid ) {
					update_post_meta( $order->get_id(), '_customer_user', $userid );
				}

				foreach ( $params['cart']['cart'] as $cart_item_key => $values ) {
					$order->add_product(
						wc_get_product( preg_replace( '/[^0-9]/i', '', $values['id'] ) ),
						preg_replace( '/[^0-9]/i', '', $values['quantity'] ),
						array(
							'variation' => $values['variation'],
							'totals'    => $values['totals'],
						)
					);
				}

				$order->set_address( $address, 'billing' );
				$order->calculate_totals();
				$order->save();

				wp_send_json(
					array(
						'status'  => ( ! empty( $order ) ? 'success' : 'error' ),
						'message' => ( ! empty( $order ) ? $order->get_data() : 'Order has been not created' ),
					)
				);
			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => 'missing fields',
						'fields'  => $missing_fields,
					)
				);
			}
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

RestApiWooCommerce::instance();
