<?php
/**
 * Restaurant Schema service for restaurant schema optimization.
 *
 * Emits a Restaurant node for posts of the `restaurant` post type. Optional
 * fields are read from post meta and omitted when empty:
 *
 * - `_saman_seo_restaurant_cuisine`          servesCuisine (comma-separated for several).
 * - `_saman_seo_restaurant_price_range`      priceRange (e.g. "$$").
 * - `_saman_seo_restaurant_telephone`        Telephone.
 * - `_saman_seo_restaurant_menu_url`         Menu URL (hasMenu).
 * - `_saman_seo_restaurant_street_address`   Address: street.
 * - `_saman_seo_restaurant_address_locality` Address: city.
 * - `_saman_seo_restaurant_address_region`   Address: state / region.
 * - `_saman_seo_restaurant_postal_code`      Address: postal code.
 * - `_saman_seo_restaurant_address_country`  Address: country.
 *
 * Use the `saman_seo_schema_restaurant` filter to supply or change data.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Restaurant Schema controller.
 */
class Restaurant_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_restaurant' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_restaurant_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add Restaurant schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_restaurant_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'restaurant';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'restaurant';
	}

	/**
	 * Build Restaurant schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		$cuisines = array_values( array_filter( array_map( 'trim', explode( ',', $this->get_field( $post, 'cuisine' ) ) ) ) );

		return array(
			'@type'         => 'Restaurant',
			'@id'           => $this->get_node_id( $post ),
			'name'          => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description'   => $this->get_description( $post ),
			'url'           => $this->get_url( $post ),
			'image'         => $this->get_image( $post ),
			'servesCuisine' => 1 === count( $cuisines ) ? $cuisines[0] : $cuisines,
			'priceRange'    => $this->get_field( $post, 'price_range' ),
			'telephone'     => $this->get_field( $post, 'telephone' ),
			'hasMenu'       => esc_url_raw( $this->get_field( $post, 'menu_url' ) ),
			'address'       => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $this->get_field( $post, 'street_address' ),
				'addressLocality' => $this->get_field( $post, 'address_locality' ),
				'addressRegion'   => $this->get_field( $post, 'address_region' ),
				'postalCode'      => $this->get_field( $post, 'postal_code' ),
				'addressCountry'  => $this->get_field( $post, 'address_country' ),
			),
		);
	}
}
