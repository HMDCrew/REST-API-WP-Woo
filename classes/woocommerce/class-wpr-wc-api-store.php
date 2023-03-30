<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPR_WC_API_Store' ) ) :
	class WPR_WC_API_Store {

		private static $instance;

		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WPR_WC_API_Store ) ) {
				self::$instance = new WPR_WC_API_Store;
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Action/filter hooks
		 */
		public function hooks() {
			add_action( 'rest_api_init', array( $this, 'wpr_rest_api_store_routes' ), 10 );
		}

		public function wpr_rest_api_store_routes( $server ) {
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
		}

		/**
		 * It removes all the data that we don't need from the product array
		 *
		 * @param array data The product data to be saved.
		 *
		 * @return array data is being returned after it has been cleaned.
		 */
		private function clean_unused_product_data( array $data ) {

			unset(
				$data['date_created'],
				$data['date_modified'],
				$data['description'],
				$data['reviews_allowed'],
				$data['post_password'],
				$data['rating_counts'],
				$data['review_count'],
				$data['virtual'],
				$data['date_on_sale_from'],
				$data['date_on_sale_to'],
				$data['tax_status'],
				$data['stock_quantity'],
				$data['manage_stock'],
				$data['weight'],
				$data['length'],
				$data['width'],
				$data['height'],
				$data['menu_order'],
				$data['meta_data'],
				$data['attributes']
			);

			return $data;
		}


		/**
		 * It takes in a bunch of arguments and returns an array of arguments that can be used to query the
		 * database for products
		 *
		 * @param int page The page number of the products to retrieve.
		 * @param int numberposts The number of products to return.
		 * @param string categories A comma-separated list of category slugs.
		 * @param string search The search term to use to filter the products.
		 * @param bool with_price If true, only products with a price will be returned.
		 * @param bool with_sku If true, only products with SKUs will be returned.
		 *
		 * @return array of get product args.
		 */
		private function prepare_products_args( int $page, int $numberposts, string $categories, string $search, bool $with_price = false, bool $with_sku = false ) {

			$args = array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'numberposts' => $numberposts,
				'fields'      => 'ids',
				'paged'       => $page,
			);

			if ( $with_sku && $with_price ) {
				$args['meta_query']['relation'] = 'AND';
			}

			if ( $with_sku ) {
				$args['meta_query'][] = array(
					'key'     => '_sku',
					'value'   => '',
					'compare' => 'NOT IN',
				);
			}

			if ( $with_price ) {
				$args['meta_query'][] = array(
					'key'     => '_price',
					'value'   => '',
					'compare' => 'NOT IN',
				);
			}

			if ( ! empty( $search ) ) {
				$args['s'] = $search;
			}

			if ( ! empty( $categories ) && isset( $categories['slug'] ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => explode( ',', $categories['slug'] ),
				);
			}

			return $args;
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
			$categories  = ( ! empty( $params['category'] ) ? preg_replace( '/[^0-9a-zA-Z\{\}\(\)\"\[\]\/\s\:\,\.\_\-]/i', '', $params['category'] ) : '' );
			$page        = ( ! empty( $params['page'] ) ? preg_replace( '/[^0-9]/i', '', $params['page'] ) : 0 );
			$search      = ( ! empty( $params['search'] ) ? preg_replace( '/[^0-9a-zA-Z\{\}\(\)\"\[\]\/\s\:\,\.\_\-]/i', '', $params['search'] ) : '' );

			$products_ids = get_posts(
				$this->prepare_products_args( $page, $numberposts, $categories, $search, true, true )
			);

			$collect_garbage = array();
			foreach ( $products_ids as $prod_id ) {
				$data = $this->clean_unused_product_data(
					(array) wc_get_product( $prod_id )->get_data()
				);

				$image             = wp_get_attachment_image_url( $data['image_id'], 'medium' );
				$data['image_uri'] = ( $image ? $image : wc_placeholder_img_src( 'medium' ) );
				$data['symbol']    = html_entity_decode( get_woocommerce_currency_symbol() );

				$collect_garbage[] = $data;
			}

			wp_send_json(
				array(
					'status'  => ( ! empty( $collect_garbage ) ? 'success' : 'error' ),
					'message' => ( ! empty( $collect_garbage ) ? $collect_garbage : "there isn't products" ),
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

			$product_data['symbol'] = html_entity_decode( get_woocommerce_currency_symbol() );

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
	}
endif;

WPR_WC_API_Store::instance();
