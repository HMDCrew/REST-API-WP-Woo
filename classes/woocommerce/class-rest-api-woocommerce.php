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
		public $update_cart;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Rest_Api_WooCommerce ) ) {
				self::$instance = new Rest_Api_WooCommerce;
				self::$instance->includes();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * It includes the files that are required for the plugin to work.
		 */
		public function includes() {
			require_once( REST_API_WORDPRESS_PLUGIN_CLASSES . 'woocommerce/cart/class-update-cart.php' );

			$this->update_cart = new Update_Cart;
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
					'methods'       => 'GET',
					'callback'      => array( $this, 'wpr_get_woocommerce_test_route_callback' ),
					'login_user_id' => get_current_user_id(),
				)
			);

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
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-product-content',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_product_content_callback' ),
				)
			);

			// Cart
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-get-cart',
				array(
					'methods'       => 'POST',
					'callback'      => array( $this, 'wpr_get_cart_callback' ),
					'login_user_id' => get_current_user_id(),
				)
			);
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-update-cart',
				array(
					'methods'       => 'POST',
					'callback'      => array( $this, 'wpr_update_cart_callback' ),
					'login_user_id' => get_current_user_id(),
				)
			);
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-add-to-cart',
				array(
					'methods'       => 'POST',
					'callback'      => array( $this, 'wpr_add_to_cart_callback' ),
					'login_user_id' => get_current_user_id(),
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

			wpr_hide_php_errors();

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
					array(
						'key'     => '_price',
						'value'   => '',
						'compare' => 'NOT IN',
					),
				),
			);

			if ( ! empty( $search ) ) {
				$args['s'] = $search;
			}

			if ( ! empty( $categorys ) && isset( $categorys['slug'] ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => explode( ',', $categorys['slug'] ),
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
				$image = wp_get_attachment_image_url( $id_img, 'full' );

				$product_data['gallery_image_ids'][ $key ] = ( $image ? $image : wc_placeholder_img_src( 'medium' ) );
			}

			$img_content_name = sprintf( '%d_post_content.jpg', $product_data['id'] );
			$img_path         = sprintf( '%s/%s', WPR_REST_API_WORDPRESS_PLUGIN_UPLOAD_DIR_PATH, $img_content_name );
			$img_url          = sprintf( '%s%s', WPR_REST_API_WORDPRESS_PLUGIN_UPLOAD_DIR_URL, $img_content_name );

			if ( ! file_exists( $img_path ) ) {
				$screen = scheen_product_content( $product_data['id'] );
				save_scheen_content( $screen['data']['screenshot_url'], $img_path );
			}

			$product_data['symbol']         = html_entity_decode( get_woocommerce_currency_symbol() );
			$product_data['content_screen'] = $img_url;

			wp_send_json(
				array(
					'status'  => ( ! empty( $product_data ) ? 'success' : 'error' ),
					'message' => ( ! empty( $product_data ) ? $product_data : 'Product not exist' ),
				)
			);
		}

		/**
		 * It takes a product ID, and returns the product's content, with the necessary CSS and JS files to
		 * make it look like the content editor in the WordPress admin
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_product_content_callback( \WP_REST_Request $request ) {

			header( 'Content-Type: text/html' );
			wpr_hide_php_errors();

			$params     = $request->get_params();
			$product_id = ( ! empty( $params['product_id'] ) ? preg_replace( '/[^0-9]/i', '', $params['product_id'] ) : 0 );

			echo wpr_get_post_content(
				$product_id,
				get_stylesheet_directory_uri() . '/assets/css/admin/rich_content.css',
				get_stylesheet_directory_uri() . '/assets/js/rich_content.js'
			);

			exit();
		}

		/**
		 * It gets the cart of the user.
		 *
		 * @param \WP_REST_Request request The request object.
		 */
		public function wpr_get_cart_callback( \WP_REST_Request $request ) {

			wpr_hide_php_errors();

			$attrs    = $request->get_attributes();
			$response = array(
				'status'  => 'error',
				'message' => 'error get_current_user_id',
			);

			if ( isset( $attrs['login_user_id'] ) ) {
				$cart = $this->update_cart->wpr_get_cart(
					intval( $attrs['login_user_id'] )
				);

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
			$attrs  = $request->get_attributes();

			$response = array(
				'status'  => 'error',
				'message' => 'error get_current_user_id',
			);

			if ( isset( $attrs['login_user_id'] ) ) {

				$update_status = $this->update_cart->update_cart_from_api(
					intval( $attrs['login_user_id'] ),
					$params['cart']
				);

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

			header( 'Content-Type: text/html' );
			wpr_hide_php_errors();

			$attrs  = $request->get_attributes();
			$params = $request->get_params();

			$product_id   = ( ! empty( $params['product_id'] ) ? preg_replace( '/[^0-9]/i', '', $params['product_id'] ) : 0 );
			$qty          = ( ! empty( $params['qty'] ) ? preg_replace( '/[^0-9]/i', '', $params['qty'] ) : 0 );
			$variation_id = ( ! empty( $params['variation_id'] ) ? preg_replace( '/[^0-9a-zA-Z\s\.\,\-\_\!\?\(\)\[\]]/i', '', $params['variation_id'] ) : 0 );

			$response = array(
				'status'  => 'error',
				'message' => 'product not added tu cart contact admin function generate error get_current_user_id',
			);

			if ( isset( $attrs['login_user_id'] ) ) {

				$user_cart_product = $this->update_cart->wpr_wc_add_to_cart(
					intval( $attrs['login_user_id'] ),
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

Rest_Api_WooCommerce::instance();
