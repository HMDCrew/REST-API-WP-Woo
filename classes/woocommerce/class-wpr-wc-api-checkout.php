<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPR_WC_API_Checkout' ) ) :
	class WPR_WC_API_Checkout {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPR_WC_API_Checkout ) ) {
				self::$instance = new WPR_WC_API_Checkout;
				self::$instance->hooks();
			}

			return self::$instance;
		}


		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_checkout_routes' ), 10 );
		}


		public function wpr_rest_api_checkout_routes( $server ) {
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-chackout-fields',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_get_chackout_fields' ),
				)
			);
			$server->register_route(
				'rest-api-wordpress',
				'/wpr-payment-gateway',
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'wpr_payment_gateway_callback' ),
				)
			);
		}

		/**
		 * It takes a list of countries and returns a list of countries and states
		 *
		 * @param array countries_list The list of countries to be used in the dropdown.
		 * @param array countries An array of countries.
		 * @param array states An array of states for the selected country.
		 */
		private function get_countries_states( array $countries_list, array $countries = array(), array $states = array() ) {

			foreach ( $countries_list as $key => $country ) {

				$countries[] = array(
					'key'   => $key,
					'value' => $country,
				);

				$wc_states = WC()->countries->get_states( $key );

				$states[ $key ] = array_map(
					function( $key, $state_name ) {
						return array(
							'key'   => $key,
							'value' => $state_name,
						);
					},
					array_keys( $wc_states ),
					$wc_states
				);
			}

			return array(
				'countries' => $countries,
				'states'    => $states,
			);
		}

		/**
		 * It gets the checkout fields from WooCommerce and sends them back to the browser as a JSON object
		 */
		public function wpr_get_chackout_fields() {

			$chackout_fields   = WC()->checkout()->checkout_fields;
			$allowed_countries = WC()->countries->get_allowed_countries();

			unset( $chackout_fields['shipping'], $chackout_fields['account'] );

			if ( empty( $allowed_countries ) ) {
				$allowed_countries = WC()->countries->get_shipping_countries();
			}

			// todo: optimazie this part adding ajax request for recover state and remove states from this list
			$chackout_fields = array_merge(
				$chackout_fields,
				$this->get_countries_states( $allowed_countries )
			);

			wp_send_json(
				array(
					'status'  => ( ! empty( $chackout_fields ) ? 'success' : 'error' ),
					'message' => ( ! empty( $chackout_fields ) ? $chackout_fields : 'Woocommerce problem with chackout fields' ),
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
	}
endif;

WPR_WC_API_Checkout::instance();
