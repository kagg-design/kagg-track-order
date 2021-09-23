<?php
/**
 * KAGG Track Order
 *
 * @package           kagg-track-order
 * @author            KAGG Design
 * @link              https://ru.stackoverflow.com/questions/1331178
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name:       KAGG Track Order
 * Description:       Plugin modifies WooCommerce Track Order form.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      5.6.20
 * Author:            KAGG Design
 * Author URI:        https://kagg.eu/en/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kagg-track-order
 * Domain Path:       /languages/
 */

/**
 * Filter woocommerce locate template.
 *
 * @param string $template      Template.
 * @param string $template_name Template name.
 * @param string $template_path Template path.
 *
 * @return string
 */
function kagg_track_order_woocommerce_locate_template( $template, $template_name, $template_path ) {
	if ( 'order/form-tracking.php' === $template_name && 'woocommerce/' === $template_path ) {
		return __DIR__ . '/' . $template_path . $template_name;
	}

	return $template;
}

add_filter( 'woocommerce_locate_template', 'kagg_track_order_woocommerce_locate_template', 10, 3 );

/**
 * Filters whether to call a shortcode callback.
 *
 * Returning a non-false value from filter will short-circuit the
 * shortcode generation process, returning that value instead.
 *
 * @param false|string $return Short-circuit return value. Either false or the value to replace the shortcode with.
 * @param string       $tag    Shortcode name.
 * @param array|string $attr   Shortcode attributes array or empty string.
 * @param array        $m      Regular expression match array.
 */
function kagg_track_order_pre_do_shortcode_tag( $return, $tag, $attr, $m ) {
	if ( 'woocommerce_order_tracking' !== $tag ) {
		return $return;
	}

	ob_start();
	kagg_track_order_output( $attr );

	return ob_get_clean();
}

add_filter( 'pre_do_shortcode_tag', 'kagg_track_order_pre_do_shortcode_tag', 10, 4 );

/**
 * Output the shortcode.
 *
 * @param array $atts Shortcode attributes.
 */
function kagg_track_order_output( $atts ) {
	// Check cart class is loaded or abort.
	if ( is_null( WC()->cart ) ) {
		return;
	}

	$atts        = shortcode_atts( [], $atts, 'woocommerce_order_tracking' );
	$nonce_value = wc_get_var( $_REQUEST['woocommerce-order-tracking-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

	if ( isset( $_REQUEST['orderid'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-order_tracking' ) ) { // WPCS: input var ok.

		$order_id = empty( $_REQUEST['orderid'] ) ? 0 : ltrim( wc_clean( wp_unslash( $_REQUEST['orderid'] ) ), '#' ); // WPCS: input var ok.

		if ( ! $order_id ) {
			wc_print_notice( __( 'Please enter a valid order ID', 'woocommerce' ), 'error' );
		} else {
			$order = wc_get_order( apply_filters( 'woocommerce_shortcode_order_tracking_order_id', $order_id ) );

			if ( $order && $order->get_id() ) {
				do_action( 'woocommerce_track_order', $order->get_id() );
				wc_get_template(
					'order/tracking.php',
					[
						'order' => $order,
					]
				);

				return;
			} else {
				wc_print_notice( __( 'Sorry, the order could not be found. Please contact us if you are having difficulty finding your order details.', 'woocommerce' ), 'error' );
			}
		}
	}

	wc_get_template( 'order/form-tracking.php' );
}
