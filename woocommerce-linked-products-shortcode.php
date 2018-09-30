<?php
/**
 * Plugin Name: WooCommerce Linked Products Shortcode
 * Plugin URI: http://bluerobotics.com
 * Description: Adds a shortcode to output WooCommerce product cross-sells and upsells; removes them from the original location when used
 * Author: Rustom Jehangir
 * Author URI: http://rstm.io
 * Version: 1.0.0
 *
 * Adapted from code from SkyVerge, Inc.
 *
 * Original Copyright: (c) 2016 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    Rustom Jehangir
 * @copyright Copyright (c) 2018, Rustom Jehangir (Adapted from SkyVerge, Inc.)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 */


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/** 
 * Adds a [woocommerce_product_cross_sells] shortcode, which outputs the cross-sells for a product
 * This shortcode will only render on product pages.
 *
 * If cross-sells are output by the shortcode, they will be removed from the default position below the description.
 *
 * The optional "columns" argument can be added to the shortcode to adjust the upsells output and styles
 */
class WC_Linked_Products_Shortcode {
	
	
	const VERSION = '1.0.0';
	

	// @var WC_Linked_Products_Shortcode single instance of this plugin
	protected static $instance;
	

	// set the column count so we can get it if overridden by the shortcode
	protected $column_count = NULL;
	protected $cross_sell_column_count = NULL;
	protected $upsell_column_count = NULL;


	public function __construct() {
		
		// add the [woocommerce_product_cross_sells] shortcode
		add_shortcode( 'woocommerce_product_cross_sells', array( $this, 'render_product_cross_sells' ) );
		
		// adjust the cross-sell display when shortcode is used
		add_filter( 'woocommerce_cross_sells_columns', array( $this, 'adjust_cross_sell_columns' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'adjust_cross_sell_column_widths' ) );

		// add the [woocommerce_product_upsells] shortcode
		add_shortcode( 'woocommerce_product_upsells', array( $this, 'render_product_upsells' ) );
		
		// adjust the upsell display when shortcode is used
		add_filter( 'woocommerce_up_sells_columns', array( $this, 'adjust_upsell_columns' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'adjust_upsell_column_widths' ) );
	}


	/** Helper methods ***************************************/


	/**
	 * Main WC_Linked_Products_Shortcode Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.0.0
	 * @see wc_linked_products_shortcode()
	 * @return WC_Linked_Products_Shortcode
	*/
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/** Plugin methods ***************************************/


	/**
	 * Set up a shortcode to output the WooCommerce product cross-sells
	 *
	 * @since 1.0.0
	 * @param array $atts shortcode attributes
	 */
	public function render_product_cross_sells( $atts ) {

		// bail if we're not on a product page
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$a = shortcode_atts( array(
			'columns'	=> '3',
		), $atts );
		
		// only use the shortcode attribute if it's an integer, otherwise fallback to 3
		$columns = (int) $a['columns'] ? $a['columns'] : 3;
	
		$this->remove_default_cross_sells();
		$this->cross_sell_column_count = $columns;
	
		// buffer our upsell display and output it with a custom CSS class
		ob_start();	

		?><div class="wc_cross_sell_shortcode"><?php	
			woocommerce_cross_sell_display();
		?></div><?php
	
		// output the buffered contents
		return ob_get_clean();
	}

	/**
	 * Set up a shortcode to output the WooCommerce product upsells
	 *
	 * @since 1.0.0
	 * @param array $atts shortcode attributes
	 */
	public function render_product_upsells( $atts ) {

		// bail if we're not on a product page
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$a = shortcode_atts( array(
			'columns'	=> '3',
		), $atts );
		
		// only use the shortcode attribute if it's an integer, otherwise fallback to 3
		$columns = (int) $a['columns'] ? $a['columns'] : 3;
	
		$this->remove_default_upsells();
		$this->upsell_column_count = $columns;
	
		// buffer our upsell display and output it with a custom CSS class
		ob_start();	

		?><div class="wc_upsell_shortcode"><?php	
			woocommerce_upsell_display();
		?></div><?php
	
		// output the buffered contents
		return ob_get_clean();
	}


	/**
	 * Adjust the number of columns to whatever the shortcode has set
	 *
	 * @since 1.0.0
	 */
	public function adjust_cross_sell_columns( $columns ) {
	
		// bail if the column count is not set, it means our shortcode isn't in use
		if ( ! isset( $this->cross_sell_column_count ) ) {
			return $columns;
		}
		
		// otherwise, set the column count to our shortcode's value
		return $this->cross_sell_column_count;
	}

	/**
	 * Adjust the number of columns to whatever the shortcode has set
	 *
	 * @since 1.0.0
	 */
	public function adjust_upsell_columns( $columns ) {
	
		// bail if the column count is not set, it means our shortcode isn't in use
		if ( ! isset( $this->upsell_column_count ) ) {
			return $columns;
		}
		
		// otherwise, set the column count to our shortcode's value
		return $this->upsell_column_count;
	}


	/**
	 * Adjust the column width CSS based on the number of columns
	 *
	 * @since 1.0.0
	 */
	public function adjust_cross_sell_column_widths () {
	
		// bail if the column count is not set, it means our shortcode isn't in use
		if ( ! isset( $this->cross_sell_column_count ) ) {
			return;
		}
	
		// set the related product width based on the number of columns + some padding
		$width = ( 100 / $this->cross_sell_column_count ) * 0.90;
	
		echo '<style>
		.woocommerce .wc_cross_sell_shortcode ul.products li.product,
		.woocommerce-page .wc_cross_sell_shortcode ul.products li.product {
			width: ' . $width . '%;
			margin-top: 1em;
		}</style>';
	
	}		

	/**
	 * Adjust the column width CSS based on the number of columns
	 *
	 * @since 1.0.0
	 */
	public function adjust_upsell_column_widths () {
	
		// bail if the column count is not set, it means our shortcode isn't in use
		if ( ! isset( $this->upsell_column_count ) ) {
			return;
		}
	
		// set the related product width based on the number of columns + some padding
		$width = ( 100 / $this->upsell_column_count ) * 0.90;
	
		echo '<style>
		.woocommerce .wc_upsell_shortcode ul.products li.product,
		.woocommerce-page .wc_upsell_shortcode ul.products li.product {
			width: ' . $width . '%;
			margin-top: 1em;
		}</style>';
	
	}
	
	/**
	 * Remove the cross-sells on the product page if shortcode is used
	 *
	 * @since 1.0.0
	 */
	private function remove_default_cross_sells() {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_cross_sell_display', 15 );
	}

	/**
	 * Remove the upsells on the product page if shortcode is used
	 *
	 * @since 1.0.0
	 */
	private function remove_default_upsells() {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	}

} // end \WC_Linked_Products_Shortcode class


/**
 * Returns the One True Instance of WC_Linked_Products_Shortcode
 *
 * @since 1.0.0
 * @return WC_Linked_Products_Shortcode
 */
function wc_linked_products_shortcode() {
	return WC_Linked_Products_Shortcode::instance();
}

/**
 * Override the default cross-sell display function.
 */
if ( ! function_exists( 'woocommerce_cross_sell_display' ) ) {

    /**
     * Output the cart cross-sells.
     *
     * @param  int    $limit (default: 2).
     * @param  int    $columns (default: 2).
     * @param  string $orderby (default: 'rand').
     * @param  string $order (default: 'desc').
     */
    function woocommerce_cross_sell_display( $limit = 2, $columns = 2, $orderby = 'rand', $order = 'desc' ) {
        global $product;

        if ( ! $product ) {
            return;
        }

        // Handle the legacy filter which controlled posts per page etc.
        $args = apply_filters( 'woocommerce_cross_sell_display_args', array(
            'posts_per_page' => $limit,
            'orderby'        => $orderby,
            'columns'        => $columns,
        ) );
        wc_set_loop_prop( 'name', 'cross-sells' );
        wc_set_loop_prop( 'columns', apply_filters( 'woocommerce_cross_sells_columns', isset( $args['columns'] ) ? $args['columns'] : $columns ) );

        $orderby = apply_filters( 'woocommerce_cross_sells_orderby', isset( $args['orderby'] ) ? $args['orderby'] : $orderby );
        $limit   = apply_filters( 'woocommerce_cross_sells_total', isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : $limit );

        // Get visible upsells then sort them at random, then limit result set.
        $cross_sells = wc_products_array_orderby( array_filter( array_map( 'wc_get_product', $product->get_cross_sell_ids() ), 'wc_products_array_filter_visible' ), $orderby, $order );
        $cross_sells = $limit > 0 ? array_slice( $cross_sells, 0, $limit ) : $cross_sells;

        wc_get_template( 'cart/cross-sells.php', array(
            'cross_sells'        => $cross_sells,

            // Not used now, but used in previous version of up-sells.php.
            'posts_per_page' => $limit,
            'orderby'        => $orderby,
            'columns'        => $columns,
        ) );
    }
}


function linked_product_new_text_strings( $translated_text, $text, $domain ) {
    switch ( $translated_text ) {
        case 'You may also like&hellip;':
            $translated_text = __( 'Related Products', 'woocommerce' ); // upsells
            break;
        case 'You may be interested in&hellip;':
            $translated_text = __( 'You May Also Need', 'woocommerce' ); // cross-sells, shown on cart
            break;					
        case 'Upsells':
            $translated_text = __( 'Related Products:', 'woocommerce' );
            break;			
        case 'Cross-sells':
            $translated_text = __( 'You May Also Need:', 'woocommerce' );
            break;						
    }
    return $translated_text;
}
// change labels
add_filter( 'gettext', 'linked_product_new_text_strings', 10, 3 );


// fire it up!
wc_linked_products_shortcode();