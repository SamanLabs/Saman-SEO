<?php
/**
 * Minimal WooCommerce / WP_Comment stand-ins for Product schema tests.
 *
 * Only the methods Product_Schema calls are implemented. Values are set
 * through public properties.
 *
 * @package Saman\SEO\Tests\Unit\Schema\Fixtures
 */

// phpcs:ignoreFile -- test doubles.

if ( ! class_exists( 'WC_DateTime' ) ) {
	/**
	 * WC_DateTime stand-in.
	 */
	class WC_DateTime extends DateTime {}
}

if ( ! class_exists( 'WC_Product' ) ) {
	/**
	 * WC_Product stand-in.
	 */
	class WC_Product {
		public $id            = 10;
		public $price         = '19.99';
		public $stock_status  = 'instock';
		public $on_sale       = false;
		public $sale_from     = null;
		public $sale_to       = null;

		public function get_id() {
			return $this->id;
		}

		public function get_price() {
			return $this->price;
		}

		public function get_stock_status() {
			return $this->stock_status;
		}

		public function is_on_sale() {
			return $this->on_sale;
		}

		public function get_date_on_sale_from() {
			return $this->sale_from;
		}

		public function get_date_on_sale_to() {
			return $this->sale_to;
		}
	}
}

if ( ! class_exists( 'WP_Comment' ) ) {
	/**
	 * WP_Comment stand-in.
	 */
	final class WP_Comment {
		public $comment_ID       = 1;
		public $comment_author   = '';
		public $comment_content  = '';
		public $comment_date_gmt = '2026-01-01 00:00:00';

		public function __construct( array $args = array() ) {
			foreach ( $args as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}
}
