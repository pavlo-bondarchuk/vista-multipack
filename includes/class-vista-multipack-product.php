<?php

defined( 'ABSPATH' ) || exit;

final class Vista_Multipack_Product {

	const META_ENABLED = '_vista_multipack_enabled';
	const META_SIZE    = '_vista_multipack_size';
	const META_PRICE   = '_vista_multipack_price';

	/**
	 * Return a validated pack configuration.
	 *
	 * @param WC_Product|int $product Product object or ID.
	 * @return array|null
	 */
	public static function get_config( $product ) {
		$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $product );

		if ( ! $product || ! $product->is_type( 'simple' ) ) {
			return null;
		}

		$product_id = $product->get_id();
		$enabled    = self::get_meta( $product_id, self::META_ENABLED );
		$size       = absint( self::get_meta( $product_id, self::META_SIZE ) );
		$price      = wc_format_decimal( self::get_meta( $product_id, self::META_PRICE ) );

		if ( 'yes' !== $enabled || $size < 2 || '' === $price || (float) $price <= 0 ) {
			return null;
		}

		return array(
			'enabled' => true,
			'size'    => $size,
			'price'   => (float) $price,
		);
	}

	/**
	 * Read translated products from the default-language product when needed.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $meta_key   Meta key.
	 * @return mixed
	 */
	private static function get_meta( $product_id, $meta_key ) {
		$value = get_post_meta( $product_id, $meta_key, true );

		if ( '' !== $value || ! has_filter( 'wpml_object_id' ) ) {
			return $value;
		}

		$default_language = apply_filters( 'wpml_default_language', null );
		$source_id        = apply_filters( 'wpml_object_id', $product_id, 'product', false, $default_language );

		if ( $source_id && (int) $source_id !== (int) $product_id ) {
			return get_post_meta( $source_id, $meta_key, true );
		}

		return $value;
	}

	/**
	 * Pack total formatted for the current storefront tax display rules.
	 *
	 * @param WC_Product $product Product.
	 * @param array      $config  Pack configuration.
	 * @return float
	 */
	public static function get_display_price( $product, array $config ) {
		return (float) wc_get_price_to_display(
			$product,
			array(
				'price' => $config['price'],
				'qty'   => 1,
			)
		);
	}

	/**
	 * URL that exposes and highlights the pack offer on the landing page.
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function get_pack_url( $product ) {
		return add_query_arg( 'vista_purchase', 'pack', $product->get_permalink() ) . '#vista-multipack';
	}
}
