<?php
/**
 * Software Schema service for software application schema optimization.
 *
 * Emits a SoftwareApplication node for posts of the `software` post type.
 * Optional fields are read from post meta and omitted when empty:
 *
 * - `_saman_seo_software_os`             operatingSystem (e.g. "Windows, macOS").
 * - `_saman_seo_software_category`       applicationCategory (e.g. "BusinessApplication").
 * - `_saman_seo_software_version`        softwareVersion.
 * - `_saman_seo_software_price`          Offer price (numeric; 0 for free).
 * - `_saman_seo_software_price_currency` Offer currency (ISO 4217, e.g. "USD").
 *
 * Use the `saman_seo_schema_software` filter to supply or change data
 * (for example aggregateRating from a reviews plugin).
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Software Schema controller.
 */
class Software_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_software' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_software_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add SoftwareApplication schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_software_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'software';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'software';
	}

	/**
	 * Build SoftwareApplication schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		$offer    = array();
		$price    = $this->get_field( $post, 'price' );
		$currency = strtoupper( $this->get_field( $post, 'price_currency' ) );

		// An offer needs both a numeric price and its currency; never guess either.
		if ( '' !== $price && is_numeric( $price ) && '' !== $currency ) {
			$offer = array(
				'@type'         => 'Offer',
				'price'         => $price,
				'priceCurrency' => $currency,
			);
		}

		return array(
			'@type'               => 'SoftwareApplication',
			'@id'                 => $this->get_node_id( $post ),
			'name'                => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description'         => $this->get_description( $post ),
			'url'                 => $this->get_url( $post ),
			'image'               => $this->get_image( $post ),
			'operatingSystem'     => $this->get_field( $post, 'os' ),
			'applicationCategory' => $this->get_field( $post, 'category' ),
			'softwareVersion'     => $this->get_field( $post, 'version' ),
			'offers'              => $offer,
		);
	}
}
