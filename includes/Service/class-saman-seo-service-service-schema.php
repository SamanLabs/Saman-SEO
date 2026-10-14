<?php
/**
 * Service Schema service for service schema optimization.
 *
 * Emits a Service node for posts of the `service` post type. Optional
 * fields are read from post meta and omitted when empty:
 *
 * - `_saman_seo_service_type`         serviceType.
 * - `_saman_seo_service_area_served`  areaServed.
 * - `_saman_seo_service_provider`     Provider name. Defaults to the site's
 *                                     Organization when the site is set up
 *                                     as an organization.
 * - `_saman_seo_service_provider_url` Provider URL (sameAs).
 *
 * Use the `saman_seo_schema_service` filter to supply or change data.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Service Schema controller.
 */
class Service_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_service' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_service_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add Service schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_service_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'service';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'service';
	}

	/**
	 * Build Service schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		$provider = $this->get_entity( $post, 'Organization', 'provider', 'provider_url' );
		if ( empty( $provider ) ) {
			$provider = $this->get_site_organization();
		}

		return array(
			'@type'       => 'Service',
			'@id'         => $this->get_node_id( $post ),
			'name'        => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description' => $this->get_description( $post ),
			'url'         => $this->get_url( $post ),
			'image'       => $this->get_image( $post ),
			'serviceType' => $this->get_field( $post, 'type' ),
			'provider'    => $provider,
			'areaServed'  => $this->get_field( $post, 'area_served' ),
		);
	}
}
