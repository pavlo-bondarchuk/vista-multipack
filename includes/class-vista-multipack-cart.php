<?php

defined( 'ABSPATH' ) || exit;

final class Vista_Multipack_Cart {

	const ITEM_MODE  = 'vista_purchase_mode';
	const ITEM_SIZE  = 'vista_pack_size';
	const ITEM_PRICE = 'vista_pack_price';

	/**
	 * Register cart, checkout and order hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_pack_request' ), 20, 3 );
		add_filter( 'woocommerce_add_to_cart_quantity', array( __CLASS__, 'expand_pack_quantity' ), 20, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 20, 3 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_pack_unit_price' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'render_cart_item_data' ), 20, 2 );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'render_cart_item_name' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'render_cart_item_price' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'render_cart_pack_quantity' ), 20, 3 );
		add_filter( 'woocommerce_stock_amount_cart_item', array( __CLASS__, 'expand_updated_cart_quantity' ), 20, 2 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( __CLASS__, 'render_mini_cart_quantity' ), 20, 3 );
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate_cart_pack_quantities' ) );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_order_item_data' ), 20, 4 );
	}

	/**
	 * Whether the current add-to-cart request selected the pack button.
	 *
	 * @return bool
	 */
	private static function is_pack_request() {
		if ( ! isset( $_REQUEST['vista_purchase_mode'] ) ) {
			return false;
		}

		$mode = sanitize_key( wp_unslash( $_REQUEST['vista_purchase_mode'] ) );
		return 'pack' === $mode;
	}

	/**
	 * Reject stale or incomplete pack submissions.
	 *
	 * @param bool $passed     Current result.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity   Requested number of packs.
	 * @return bool
	 */
	public static function validate_pack_request( $passed, $product_id, $quantity ) {
		if ( ! self::is_pack_request() ) {
			return $passed;
		}

		$config = Vista_Multipack_Product::get_config( $product_id );
		if ( ! $config ) {
			wc_add_notice( __( 'This set offer is no longer available.', 'vista-multipack' ), 'error' );
			return false;
		}

		$product       = wc_get_product( $product_id );
		$unit_quantity = max( 1, wc_stock_amount( $quantity ) ) * $config['size'];

		if ( $product && $product->managing_stock() && ! $product->has_enough_stock( $unit_quantity ) ) {
			wc_add_notice( __( 'There is not enough stock for the selected number of sets.', 'vista-multipack' ), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Convert submitted packs to real product units.
	 *
	 * @param int|float $quantity   Requested pack quantity.
	 * @param int       $product_id Product ID.
	 * @return int|float
	 */
	public static function expand_pack_quantity( $quantity, $product_id ) {
		if ( ! self::is_pack_request() ) {
			return $quantity;
		}

		$config = Vista_Multipack_Product::get_config( $product_id );
		return $config ? $quantity * $config['size'] : $quantity;
	}

	/**
	 * Make pack and unit purchases separate cart lines.
	 *
	 * @param array $cart_item_data Existing data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID.
	 * @return array
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		unset( $variation_id );

		if ( ! self::is_pack_request() ) {
			return $cart_item_data;
		}

		$config = Vista_Multipack_Product::get_config( $product_id );
		if ( ! $config ) {
			return $cart_item_data;
		}

		$cart_item_data[ self::ITEM_MODE ]  = 'pack';
		$cart_item_data[ self::ITEM_SIZE ]  = $config['size'];
		$cart_item_data[ self::ITEM_PRICE ] = $config['price'];

		return $cart_item_data;
	}

	/**
	 * Convert total pack price into the per-unit amount WooCommerce calculates.
	 *
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public static function apply_pack_unit_price( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! self::is_pack_item( $cart_item ) || empty( $cart_item['data'] ) ) {
				continue;
			}

			$unit_price = (float) $cart_item[ self::ITEM_PRICE ] / (int) $cart_item[ self::ITEM_SIZE ];
			$cart_item['data']->set_price( $unit_price );
		}
	}

	/**
	 * Show pack information below the cart item name.
	 *
	 * @param array $item_data Existing display data.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public static function render_cart_item_data( $item_data, $cart_item ) {
		if ( ! self::is_pack_item( $cart_item ) ) {
			return $item_data;
		}

		$item_data[] = array(
			'key'   => __( 'Purchase option', 'vista-multipack' ),
			'value' => sprintf(
				/* translators: %d: number of units in one pack. */
				_n( 'Set of %d unit', 'Set of %d units', $cart_item[ self::ITEM_SIZE ], 'vista-multipack' ),
				$cart_item[ self::ITEM_SIZE ]
			),
		);

		return $item_data;
	}

	/**
	 * Distinguish the pack line visually.
	 *
	 * @param string $name          Product name.
	 * @param array  $cart_item     Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function render_cart_item_name( $name, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );
		return self::is_pack_item( $cart_item )
			? $name . ' <span class="vista-multipack-cart-badge">' . esc_html__( 'Set', 'vista-multipack' ) . '</span>'
			: $name;
	}

	/**
	 * Show the price of one pack rather than the internal per-unit price.
	 *
	 * @param string $price         Existing price HTML.
	 * @param array  $cart_item     Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function render_cart_item_price( $price, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		if ( ! self::is_pack_item( $cart_item ) ) {
			return $price;
		}

		$product = $cart_item['data'];
		$total   = wc_get_price_to_display(
			$product,
			array(
				'price' => (float) $cart_item[ self::ITEM_PRICE ],
				'qty'   => 1,
			)
		);

		return wc_price( $total ) . ' <small>' . esc_html__( 'per set', 'vista-multipack' ) . '</small>';
	}

	/**
	 * Render a pack-count input while keeping unit quantities in WooCommerce.
	 *
	 * @param string $html          Existing quantity HTML.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $cart_item     Cart item.
	 * @return string
	 */
	public static function render_cart_pack_quantity( $html, $cart_item_key, $cart_item ) {
		if ( ! self::is_pack_item( $cart_item ) ) {
			return $html;
		}

		$size       = (int) $cart_item[ self::ITEM_SIZE ];
		$pack_count = (int) $cart_item['quantity'] / $size;
		$product    = $cart_item['data'];
		$max_units  = $product->get_max_purchase_quantity();
		$max_packs  = $max_units > 0 ? (int) floor( $max_units / $size ) : 0;

		$input = woocommerce_quantity_input(
			array(
				'input_name'   => "cart[{$cart_item_key}][qty]",
				'input_value'  => max( 1, $pack_count ),
				'max_value'    => $max_packs,
				'min_value'    => 0,
				'step'         => 1,
				'product_name' => $product->get_name(),
			),
			$product,
			false
		);

		return $input . '<small class="vista-multipack-cart-quantity">' .
			esc_html(
				sprintf(
					/* translators: %d: real number of product units. */
					_n( '%d unit total', '%d units total', $cart_item['quantity'], 'vista-multipack' ),
					$cart_item['quantity']
				)
			) .
			'</small>';
	}

	/**
	 * Convert a cart form's pack count back to real units before validation.
	 *
	 * @param int|float $amount        Submitted amount.
	 * @param string    $cart_item_key Cart item key.
	 * @return int|float
	 */
	public static function expand_updated_cart_quantity( $amount, $cart_item_key ) {
		if ( ! WC()->cart ) {
			return $amount;
		}

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		return self::is_pack_item( $cart_item )
			? $amount * (int) $cart_item[ self::ITEM_SIZE ]
			: $amount;
	}

	/**
	 * Show pack counts in the classic mini cart.
	 *
	 * @param string $html          Existing mini-cart quantity.
	 * @param array  $cart_item     Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function render_mini_cart_quantity( $html, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		if ( ! self::is_pack_item( $cart_item ) ) {
			return $html;
		}

		$size       = (int) $cart_item[ self::ITEM_SIZE ];
		$pack_count = (int) $cart_item['quantity'] / $size;

		return '<span class="quantity">' .
			esc_html(
				sprintf(
					/* translators: 1: pack count, 2: units per pack. */
					__( '%1$d set(s) × %2$d units', 'vista-multipack' ),
					$pack_count,
					$size
				)
			) .
			'</span>';
	}

	/**
	 * Protect pack integrity if another extension changes quantities.
	 *
	 * @return void
	 */
	public static function validate_cart_pack_quantities() {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! self::is_pack_item( $cart_item ) ) {
				continue;
			}

			$size = (int) $cart_item[ self::ITEM_SIZE ];
			if ( $size < 2 || (int) $cart_item['quantity'] % $size !== 0 ) {
				wc_add_notice( __( 'A set quantity was changed to an invalid number of units. Please remove it and add the set again.', 'vista-multipack' ), 'error' );
			}
		}
	}

	/**
	 * Preserve human and technical pack data on the order line.
	 *
	 * The order quantity remains the real number of units, which keeps stock,
	 * refunds and external order integrations consistent.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart values.
	 * @param WC_Order              $order         Order.
	 * @return void
	 */
	public static function add_order_item_data( $item, $cart_item_key, $values, $order ) {
		unset( $cart_item_key, $order );

		if ( ! self::is_pack_item( $values ) ) {
			return;
		}

		$size       = (int) $values[ self::ITEM_SIZE ];
		$pack_count = (int) $values['quantity'] / $size;

		$item->add_meta_data( '_vista_purchase_mode', 'pack', true );
		$item->add_meta_data( '_vista_pack_size', $size, true );
		$item->add_meta_data( '_vista_pack_price', wc_format_decimal( $values[ self::ITEM_PRICE ] ), true );
		$item->add_meta_data(
			__( 'Set', 'vista-multipack' ),
			sprintf(
				/* translators: 1: number of packs, 2: units in each pack. */
				__( '%1$d × %2$d units', 'vista-multipack' ),
				$pack_count,
				$size
			),
			true
		);
	}

	/**
	 * Validate a cart item's saved pack snapshot.
	 *
	 * @param array|false $cart_item Cart item.
	 * @return bool
	 */
	private static function is_pack_item( $cart_item ) {
		return is_array( $cart_item )
			&& isset( $cart_item[ self::ITEM_MODE ], $cart_item[ self::ITEM_SIZE ], $cart_item[ self::ITEM_PRICE ] )
			&& 'pack' === $cart_item[ self::ITEM_MODE ]
			&& (int) $cart_item[ self::ITEM_SIZE ] >= 2
			&& (float) $cart_item[ self::ITEM_PRICE ] > 0;
	}
}
