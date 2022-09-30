<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UpdateCart' ) ) :

	class UpdateCart {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof UpdateCart ) ) {
				self::$instance = new UpdateCart;
			}

			return self::$instance;
		}

		public function update_item_qty( $item ) {

			// Update cart Qty
			$threeball_product_values   = WC()->cart->get_cart_item( $item['key'] );
			$threeball_product_quantity = apply_filters( 'woocommerce_stock_amount_cart_item', apply_filters( 'woocommerce_stock_amount', preg_replace( '/[^0-9\.]/', '', filter_var( $item['quantity'], FILTER_SANITIZE_NUMBER_INT ) ) ), $item['key'] );

			$passed_validation = apply_filters( 'woocommerce_update_cart_validation', true, $item['key'], $threeball_product_values, $threeball_product_quantity );

			if ( $passed_validation ) {
				WC()->cart->set_quantity( $item['key'], $threeball_product_quantity, true );
				return true;
			}

			return false;
		}
	}

endif;

UpdateCart::instance();
