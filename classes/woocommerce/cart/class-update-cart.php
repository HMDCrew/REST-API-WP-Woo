<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Update_Cart' ) ) :

	class Update_Cart {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Update_Cart ) ) {
				self::$instance = new Update_Cart;
			}

			return self::$instance;
		}

		/**
		 * It updates the quantity of a product in the cart
		 *
		 * @param mixed item This is the item that you want to update. It's an array with the following keys:
		 *
		 * @return bool
		 */
		private function update_item_qty( mixed $item ) {

			$qty = preg_replace( '/[^0-9\.\,]/', '', filter_var( $item['quantity'], FILTER_SANITIZE_NUMBER_INT ) );
			$key = preg_replace( '/[^a-zA-Z0-9\-\_\.\,]/', '', $item['key'] );

			$product_values    = WC()->cart->get_cart_item( $key );
			$product_quantity  = apply_filters( 'woocommerce_stock_amount_cart_item', apply_filters( 'woocommerce_stock_amount', $qty ), $key );
			$passed_validation = apply_filters( 'woocommerce_update_cart_validation', true, $key, $product_values, $product_quantity );

			if ( $passed_validation ) {
				WC()->cart->set_quantity( $key, $product_quantity, true );
				return true;
			}

			return false;
		}

		/**
		 * It takes a user ID and an array of items, and updates the cart for that user with the items in the
		 * array
		 *
		 * @param int user_id The user ID of the user whose cart you want to update.
		 * @param array cart An array of items in the cart.
		 *
		 * @return array
		 */
		public function update_cart_from_api( int $user_id, array $cart ) {

			WC()->customer = new WC_Customer( $user_id, true );

			foreach ( $cart as $item ) {
				$this->update_item_qty( $item );
			}

			return array(
				'user_id' => $user_id,
				'items'   => array_values(
					$this->prepare_api_cart( WC()->cart->get_cart(), array( 'data' ) )
				),
			);
		}

		/**
		 * It removes the 'data' key from the  array
		 *
		 * @param array cart_product The cart product array.
		 * @param array remove An array of keys to remove from the cart product.
		 *
		 * @return array of cart products with the data key removed.
		 */
		private function clean_cart_product_for_meta( array $cart_product, array $remove = array( 'data' ) ) {
			return array_diff_key( $cart_product, array_flip( $remove ) );
		}

		/**
		 * It takes a product array and a product object, and returns a product array with some extra data
		 *
		 * @param array product The product array that is passed to the function.
		 * @param mixed data The product data.
		 *
		 * @return array product is being returned.
		 */
		private function prepare_api_product( array $product, mixed $data ) {

			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product['product_id'] ), 'single-post-thumbnail' );

			$product['image']   = $image ? reset( $image ) : null;
			$product['name']    = $data->get_name();
			$product['price']   = $data->get_price( $data );
			$product['max_qty'] = $data->get_stock_quantity();
			$product['symbol']  = trim( html_entity_decode( get_woocommerce_currency_symbol() ) );

			return $product;
		}

		/**
		 * It prepares the cart for the API.
		 *
		 * @param mixed cart The cart object
		 * @param array remove_product_key_content This is an array of keys that you want to remove from the
		 * product object.
		 * @param array cart_callback This is the array that will be returned.
		 *
		 * @return array cart_callback is being returned.
		 */
		private function prepare_api_cart( mixed $cart, array $remove_product_key_content, array $cart_callback = array() ) {

			foreach ( $cart as $key => $cart_product ) {
				$cart_callback[ $key ] = $this->prepare_api_product(
					$this->clean_cart_product_for_meta( $cart_product, $remove_product_key_content ),
					$cart_product['data']
				);
			}

			return $cart_callback;
		}

		/**
		 * It returns an array of items in the cart
		 *
		 * @param int user_id The user ID of the user whose cart you want to retrieve.
		 *
		 * @return array user_id and the items in the cart.
		 */
		public function wpr_get_cart( int $user_id ) {

			WC()->customer = new WC_Customer( $user_id, true );

			return array(
				'user_id' => $user_id,
				'items'   => array_values(
					$this->prepare_api_cart( WC()->cart->get_cart(), array( 'data' ) )
				),
			);
		}

		/**
		 * It adds a product to the cart of a user
		 *
		 * @param int user_id The user ID of the user you want to add the product to.
		 * @param int product_id The product ID of the product you want to add to the cart.
		 * @param int qty The quantity of the product to add to the cart.
		 * @param int variation_id The variation ID of the product.
		 *
		 * @return array|bool array of the user_id, cart_item_key, and items.
		 */
		public function wpr_wc_add_to_cart( int $user_id, int $product_id, int $qty, int $variation_id ) {

			if ( $user_id > 0 && $product_id && $qty ) {

				WC()->customer = new WC_Customer( $user_id, true );
				$cart_item_key = WC()->cart->add_to_cart( $product_id, $qty, $variation_id );

				$cart_for_meta = $this->prepare_api_cart(
					WC()->cart->get_cart(),
					array(
						'line_tax_data',
						'line_subtotal',
						'line_subtotal_tax',
						'line_total',
						'line_tax',
						'data',
					)
				);

				update_user_meta( $user_id, '_woocommerce_persistent_cart_1', array( 'cart' => $cart_for_meta ) );

				return array(
					'user_id'       => $user_id,
					'cart_item_key' => $cart_item_key,
					'items'         => array_values( $cart_for_meta ),
				);
			}

			return false;
		}
	}

endif;

Update_Cart::instance();
