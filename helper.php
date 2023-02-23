<?php

if ( ! function_exists( 'sub_category_mapping' ) ) {
	/**
	 * It takes an array of terms and returns an array of terms with sub-terms
	 *
	 * @link https://stackoverflow.com/questions/4843945/php-tree-structure-for-categories-and-sub-categories-without-looping-a-query
	 *
	 * @param terms The terms you want to map.
	 */
	function sub_category_mapping( $terms ) {

		$terms = json_decode(
			json_encode(
				$terms
			)
		);

		$childs = array();

		foreach ( $terms as $item ) {
			$childs[ $item->parent ][] = $item;
		}

		foreach ( $terms as $item ) {
			if ( isset( $childs[ $item->term_id ] ) ) {
				$item->sub_terms = $childs[ $item->term_id ];
			}
		}

		return $childs[0];
	}
}

if ( ! function_exists( 'validate_chackout_fields' ) ) {
	/**
	 * It takes an array of form fields and returns an array of missing fields
	 *
	 * @param form_fields An array of form fields.
	 *
	 * @return array of missing fields.
	 */
	function validate_chackout_fields( $form_fields ) {

		$chackout_fields = WC()->checkout()->checkout_fields['billing'];

		$form_fields = array_merge(
			...array_map(
				function( $el ) {
					$name  = preg_replace( '/[^a-zA-Z0-9\_\-]/i', '', $el['name'] );
					$value = preg_replace( '/[^a-zA-Z0-9\.\@\_\-\s]/i', '', $el['value'] );
					return array( $name => $value );
				},
				$form_fields
			)
		);

		$missing_fields = array();
		foreach ( $chackout_fields as $key => $field ) {
			if ( $field['required'] && ! in_array( $key, array_keys( $form_fields ), true ) ) {
				array_push( $missing_fields, $key );
			}
		}

		return $missing_fields;
	}
}

if ( ! function_exists( 'scheen_post_content' ) ) {
	/**
	 * It takes a post ID, and returns a screenshot of the post's content
	 *
	 * @param int post_id The ID of the post you want to screenshot.
	 *
	 * @return array.
	 */
	function scheen_post_content( int $post_id ) {
		return wpr_get_url_screen(
			sprintf( '%s/wp-json/wpr-get-post-content?post_id=%d', home_url(), $post_id )
		);
	}
}
if ( ! function_exists( 'scheen_product_content' ) ) {
	/**
	 * It takes a product ID, and returns a screenshot of the post's content
	 *
	 * @param int product_id The ID of the post you want to screenshot.
	 *
	 * @return array.
	 */
	function scheen_product_content( int $product_id ) {
		return wpr_get_url_screen(
			sprintf( '%s/wp-json/wpr-get-product-content?product_id=%d', home_url(), $product_id )
		);
	}
}

if ( ! function_exists( 'wpr_get_url_screen' ) ) {
	/**
	 * It takes a URL and returns a screenshot of that URL
	 *
	 * @param string url The URL of the page you want to screenshot.
	 *
	 * @return array response from the API.
	 */

	// API key https://rapidapi.com/Cobbex/api/screen-it2
	function wpr_get_url_screen( string $url ) {

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL            => 'https://screen-it2.p.rapidapi.com/screenshot',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_ENCODING       => '',
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => 'POST',
				CURLOPT_POSTFIELDS     => json_encode(
					array(
						'url'     => $url,
						'delay'   => 0,
						'timeout' => 60,
						'width'   => 800,
						'height'  => 1080,
						'crop'    => false,
						'format'  => 'jpeg',
						'fresh'   => true,
					)
				),
				CURLOPT_HTTPHEADER     => array(
					'X-RapidAPI-Host: screen-it2.p.rapidapi.com',
					'X-RapidAPI-Key: API KEY',
					'content-type: application/json',
				),
			)
		);

		$response = curl_exec( $curl );
		$err      = curl_error( $curl );

		curl_close( $curl );

		if ( $err ) {
			return array( 'error' => $err );
		} else {
			return json_decode( $response, true );
		}
	}
}


if ( ! function_exists( 'save_scheen_content' ) ) {
	/**
	 * It downloads a file from a URL and saves it to a destination
	 *
	 * @param string url The URL of the screenshot to save.
	 * @param string dest The destination file path.
	 *
	 * @return string file path of the saved file.
	 */
	function save_scheen_content( string $url, string $dest ) {

		$fp = fopen( $dest, 'w+' );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

		$result = curl_exec( $ch );
		curl_close( $ch );

		fclose( $fp );

		return $result ? $dest : '';
	}
}

if ( ! function_exists( 'wpr_get_post_content' ) ) {
	/**
	 * It takes a post ID, and returns the post content as a full HTML document
	 *
	 * @param int post_id The ID of the post you want to get the content from.
	 * @param string css_path The path to the CSS file you want to include.
	 * @param string js_path The path to the JavaScript file you want to include.
	 */
	function wpr_get_post_content( int $post_id, string $css_path = '', string $js_path = '' ) {

		$post = get_post( $post_id );

		if ( ! empty( $css_path ) ) {
			$css_path = sprintf( '<link type="text/css" rel="stylesheet" href="%s">', $css_path );
		}

		if ( ! empty( $js_path ) ) {
			$js_path = sprintf( '<script src="%s"></script>', $js_path );
		}

		return sprintf(
			'<html><head>%s</head><body>%s%s</body></html>',
			$css_path,
			$post->post_content,
			$js_path
		);
	}
}


if ( ! function_exists( 'wpr_hide_php_errors' ) ) {
	/**
	 * It hides PHP errors.
	 */
	function wpr_hide_php_errors() {
		error_reporting( 0 );
		ini_set( 'display_errors', false );
	}
}
