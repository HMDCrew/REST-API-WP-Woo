<?php

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


/**
 * It takes an array of form fields and returns an array of missing fields
 *
 * @param form_fields An array of form fields.
 *
 * @return An array of missing fields.
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
