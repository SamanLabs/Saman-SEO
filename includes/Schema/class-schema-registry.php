<?php
/**
 * Schema Registry class.
 *
 * Central registry for schema type registration and instantiation.
 * Provides extensibility for third-party schema types.
 *
 * @package Saman\SEO\Schema
 * @since   1.0.0
 */

namespace Saman\SEO\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton registry for schema type management.
 *
 * Stores schema type definitions (slug => config) and provides methods
 * to register, retrieve, and instantiate schema types. Third-party
 * developers can register custom schema types via the register() method.
 */
class Schema_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Schema_Registry|null
	 */
	private static ?Schema_Registry $instance = null;

	/**
	 * Registered schema types.
	 *
	 * @var array<string, array> Type slug => config array.
	 */
	private array $types = [];

	/**
	 * Private constructor to enforce singleton pattern.
	 *
	 * Use Schema_Registry::instance() to get the singleton.
	 */
	private function __construct() {
		// Private constructor prevents direct instantiation.
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Schema_Registry The singleton instance.
	 */
	public static function instance(): Schema_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register a schema type.
	 *
	 * Registers a schema type for use in the graph. Third-party developers
	 * can use this to add custom schema types.
	 *
	 * @param string $slug       Unique type identifier (e.g., 'article', 'product').
	 * @param string $class_name Fully qualified class name extending Abstract_Schema.
	 * @param array  $args       Optional configuration.
	 *                           - label: Human-readable label (default: ucfirst($slug))
	 *                           - post_types: Array of post types this schema applies to
	 *                           - priority: Processing order (default: 10, lower = earlier)
	 * @return void
	 */
	public function register( string $slug, string $class_name, array $args = [] ): void {
		$this->types[ $slug ] = [
			'class'      => $class_name,
			'label'      => $args['label'] ?? ucfirst( $slug ),
			'post_types' => $args['post_types'] ?? [],
			'priority'   => $args['priority'] ?? 10,
		];

		/**
		 * Fires after a schema type is registered.
		 *
		 * @param string $slug       The schema type slug.
		 * @param string $class_name The schema class name.
		 * @param array  $args       The registration arguments.
		 */
		do_action( 'saman_seo_schema_type_registered', $slug, $class_name, $args );
	}

	/**
	 * Get all registered schema types.
	 *
	 * @return array<string, array> All registered types, filterable via saman_seo_schema_types.
	 */
	public function get_types(): array {
		/**
		 * Filter the registered schema types.
		 *
		 * @param array $types All registered schema types.
		 */
		return apply_filters( 'saman_seo_schema_types', $this->types );
	}

	/**
	 * Check if a schema type is registered.
	 *
	 * @param string $slug Type slug to check.
	 * @return bool True if registered, false otherwise.
	 */
	public function has( string $slug ): bool {
		return isset( $this->types[ $slug ] );
	}

	/**
	 * Create schema instance for a registered type.
	 *
	 * Instantiates the schema class for the given type with the provided context.
	 *
	 * @param string         $slug    Type slug.
	 * @param Schema_Context $context Context object for the schema.
	 * @return Abstract_Schema|null The schema instance, or null if type not registered.
	 */
	public function make( string $slug, Schema_Context $context ): ?Abstract_Schema {
		if ( ! $this->has( $slug ) ) {
			return null;
		}

		$class = $this->types[ $slug ]['class'];
		return new $class( $context );
	}

	/**
	 * Get configuration for a registered type.
	 *
	 * Returns the full configuration array for a registered type,
	 * useful for getting label, post_types, priority settings.
	 *
	 * @param string $slug Type slug.
	 * @return array|null The type configuration, or null if not registered.
	 */
	public function get( string $slug ): ?array {
		return $this->types[ $slug ] ?? null;
	}
}
