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
