<?php
/**
 * Assistants REST Controller
 *
 * Simplified controller that delegates AI chat to Saman Labs AI.
 * Keeps custom assistants CRUD and usage tracking local.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Integration\AI_Pilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for AI assistants.
 * Chat is handled by Saman Labs AI. Custom assistants CRUD remains local.
 */
class Assistants_Controller extends REST_Controller {

	/**
	 * Custom assistants table name.
	 *
	 * @var string
	 */
	private $custom_assistants_table;

	/**
	 * Usage tracking table name.
	 *
	 * @var string
	 */
	private $usage_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->custom_assistants_table = $wpdb->prefix . 'SAMAN_SEO_custom_assistants';
		$this->usage_table             = $wpdb->prefix . 'SAMAN_SEO_assistant_usage';
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Get all assistants.
		register_rest_route( $this->namespace, '/assistants', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_assistants' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		// Chat with assistant (delegates to Saman Labs AI).
		register_rest_route( $this->namespace, '/assistants/chat', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'chat' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		// === Custom Assistants CRUD ===
		register_rest_route( $this->namespace, '/assistants/custom', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_custom_assistants' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_custom_assistant' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		register_rest_route( $this->namespace, '/assistants/custom/(?P<id>\d+)', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_custom_assistant' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_custom_assistant' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_custom_assistant' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		// Usage stats.
		register_rest_route( $this->namespace, '/assistants/stats', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_usage_stats' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );
	}

	/**
	 * Get all available assistants.
	 * Returns built-in SEO assistants registered with Saman Labs AI + custom assistants.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_assistants( $request ) {
		$assistants = [];

		// Add built-in SEO assistants (registered with Saman Labs AI).
		$assistants[] = [
			'id'                => 'seo-general',
			'name'              => __( 'SEO Assistant', 'saman-seo' ),
			'description'       => __( 'Your helpful SEO buddy for all things search optimization.', 'saman-seo' ),
			'initial_message'   => __( "Hey! I'm your SEO assistant. Ask me about meta tags, keywords, content optimization, or anything SEO-related.", 'saman-seo' ),
			'suggested_prompts' => [
				__( 'How do I write a good meta description?', 'saman-seo' ),
				__( 'What makes a title tag effective?', 'saman-seo' ),
				__( 'Help me find keywords for my blog post', 'saman-seo' ),
				__( 'What are internal links and why do they matter?', 'saman-seo' ),
			],
			'is_builtin'        => true,
			'color'             => '#3b82f6',
			'icon'              => 'ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢Ãƒâ€šÃ‚Â¬',
		];

		$assistants[] = [
			'id'                => 'seo-reporter',
			'name'              => __( 'SEO Reporter', 'saman-seo' ),
			'description'       => __( 'Your weekly SEO buddy that gives you the rundown on your site.', 'saman-seo' ),
			'initial_message'   => __( "Hey! I can give you a quick rundown of your site's SEO health. Want me to take a look?", 'saman-seo' ),
			'suggested_prompts' => [
				__( 'Give me a quick SEO report', 'saman-seo' ),
				__( 'What SEO issues should I fix first?', 'saman-seo' ),
				__( 'Check my meta titles and descriptions', 'saman-seo' ),
				__( 'Find posts missing SEO data', 'saman-seo' ),
			],
			'is_builtin'        => true,
			'color'             => '#8b5cf6',
			'icon'              => 'ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒâ€¦Ã‚Â ',
		];

		// Add custom assistants.
		$custom = $this->get_custom_assistants_list();
		foreach ( $custom as $ca ) {
			if ( $ca['is_active'] ) {
				$assistants[] = [
					'id'                => 'custom_' . $ca['id'],
					'name'              => $ca['name'],
					'description'       => $ca['description'],
					'initial_message'   => $ca['initial_message'] ?? '',
					'suggested_prompts' => json_decode( $ca['suggested_prompts'] ?? '[]', true ),
					'is_builtin'        => false,
					'is_custom'         => true,
					'custom_id'         => $ca['id'],
					'color'             => $ca['color'] ?? '#6366f1',
					'icon'              => $ca['icon'] ?? 'ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸Ãƒâ€šÃ‚Â¤ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“',
				];
			}
		}

		return $this->success( $assistants );
	}

	/**
	 * Chat with an assistant.
	 * Delegates to Saman Labs AI for AI processing.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function chat( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$assistant_id = isset( $params['assistant'] ) ? sanitize_text_field( $params['assistant'] ) : '';
		$message      = isset( $params['message'] ) ? sanitize_textarea_field( $params['message'] ) : '';
		$context      = isset( $params['context'] ) ? $params['context'] : [];

		if ( empty( $assistant_id ) ) {
			return $this->error( __( 'Assistant ID is required.', 'saman-seo' ), 'missing_assistant', 400 );
		}

		if ( empty( $message ) ) {
			return $this->error( __( 'Message is required.', 'saman-seo' ), 'missing_message', 400 );
		}

		// Check if Saman Labs AI is ready.
		if ( ! AI_Pilot::is_ready() ) {
			$status = AI_Pilot::get_status();

			if ( ! $status['installed'] ) {
				return $this->error(
					__( 'Saman Labs AI is required for AI assistants. Please install it from the More page.', 'saman-seo' ),
					'ai_not_installed',
					400
				);
			}

			return $this->error(
				__( 'Saman Labs AI needs configuration. Please add an API key in Saman Labs AI settings.', 'saman-seo' ),
				'ai_not_configured',
				400
			);
		}

		// Handle custom assistants.
		if ( strpos( $assistant_id, 'custom_' ) === 0 ) {
			return $this->chat_with_custom_assistant( $assistant_id, $message, $context );
		}

		// Use Saman Labs AI for built-in assistants.
		$response = AI_Pilot::assistant_chat( $assistant_id, $message, $context );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), 'ai_error', 500 );
		}

		// Track usage locally.
		$this->track_usage( $assistant_id );

		return $this->success( [
			'message' => $response['content'] ?? $response['message'] ?? $response,
			'actions' => [],
		] );
	}

	/**
	 * Handle chat with custom assistant.
	 *
	 * @param string $assistant_id Assistant ID (custom_123 format).
	 * @param string $message      User message.
	 * @param array  $context      Context data.
	 * @return \WP_REST_Response
	 */
	private function chat_with_custom_assistant( $assistant_id, $message, $context ) {
		$custom_id = intval( str_replace( 'custom_', '', $assistant_id ) );
		$assistant = $this->get_custom_assistant_by_id( $custom_id );

		if ( ! $assistant ) {
			return $this->error( __( 'Custom assistant not found.', 'saman-seo' ), 'not_found', 404 );
		}

		if ( ! $assistant['is_active'] ) {
			return $this->error( __( 'This assistant is not active.', 'saman-seo' ), 'inactive', 400 );
		}

		// Build messages for chat.
		$messages = [
			[
				'role'    => 'system',
				'content' => $assistant['system_prompt'],
			],
			[
				'role'    => 'user',
				'content' => $message,
			],
		];

		// Use Saman Labs AI for the actual chat.
		$response = AI_Pilot::chat( $messages );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), 'ai_error', 500 );
		}

		// Track usage.
		$this->track_usage( $assistant_id );

		return $this->success( [
			'message' => $response['content'] ?? $response,
			'actions' => [],
		] );
	}

	// =========================================================================
	// CUSTOM ASSISTANTS CRUD
	// =========================================================================

	/**
	 * Get all custom assistants.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_custom_assistants( $request ) {
		$assistants = $this->get_custom_assistants_list();

		// Add usage stats to each assistant.
		foreach ( $assistants as &$assistant ) {
			$assistant['usage'] = $this->get_assistant_usage_count( 'custom_' . $assistant['id'] );
		}

		return $this->success( $assistants );
	}

	/**
	 * Get a single custom assistant.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_custom_assistant( $request ) {
		$id        = intval( $request->get_param( 'id' ) );
		$assistant = $this->get_custom_assistant_by_id( $id );

		if ( ! $assistant ) {
			return $this->error( __( 'Assistant not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$assistant['usage'] = $this->get_assistant_usage_count( 'custom_' . $id );

		return $this->success( $assistant );
	}

	/**
	 * Create a custom assistant.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function create_custom_assistant( $request ) {
		global $wpdb;

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		if ( empty( $params['name'] ) ) {
			return $this->error( __( 'Name is required.', 'saman-seo' ), 'missing_name', 400 );
		}

		if ( empty( $params['system_prompt'] ) ) {
			return $this->error( __( 'System prompt is required.', 'saman-seo' ), 'missing_prompt', 400 );
		}

		$this->maybe_create_assistants_table();

		$data = [
			'name'              => sanitize_text_field( $params['name'] ),
			'description'       => sanitize_textarea_field( $params['description'] ?? '' ),
			'system_prompt'     => sanitize_textarea_field( $params['system_prompt'] ),
			'initial_message'   => sanitize_textarea_field( $params['initial_message'] ?? '' ),
			'suggested_prompts' => wp_json_encode( $params['suggested_prompts'] ?? [] ),
			'icon'              => sanitize_text_field( $params['icon'] ?? 'ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸Ãƒâ€šÃ‚Â¤ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“' ),
			'color'             => sanitize_hex_color( $params['color'] ?? '#6366f1' ) ?: '#6366f1',
			'model_id'          => sanitize_text_field( $params['model_id'] ?? '' ),
			'is_active'         => isset( $params['is_active'] ) ? ( $params['is_active'] ? 1 : 0 ) : 1,
			'created_at'        => current_time( 'mysql' ),
			'updated_at'        => current_time( 'mysql' ),
		];

		$result = $wpdb->insert( $this->custom_assistants_table, $data );

		if ( false === $result ) {
			return $this->error( __( 'Failed to create assistant.', 'saman-seo' ), 'db_error', 500 );
		}

		return $this->success( [ 'id' => $wpdb->insert_id ], __( 'Assistant created successfully.', 'saman-seo' ) );
	}

	/**
	 * Update a custom assistant.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_custom_assistant( $request ) {
		global $wpdb;

		$id       = intval( $request->get_param( 'id' ) );
		$existing = $this->get_custom_assistant_by_id( $id );

		if ( ! $existing ) {
			return $this->error( __( 'Assistant not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$data = [ 'updated_at' => current_time( 'mysql' ) ];

		if ( isset( $params['name'] ) ) {
			$data['name'] = sanitize_text_field( $params['name'] );
		}
		if ( isset( $params['description'] ) ) {
			$data['description'] = sanitize_textarea_field( $params['description'] );
		}
		if ( isset( $params['system_prompt'] ) ) {
			$data['system_prompt'] = sanitize_textarea_field( $params['system_prompt'] );
		}
		if ( isset( $params['initial_message'] ) ) {
			$data['initial_message'] = sanitize_textarea_field( $params['initial_message'] );
		}
		if ( isset( $params['suggested_prompts'] ) ) {
			$data['suggested_prompts'] = wp_json_encode( $params['suggested_prompts'] );
		}
		if ( isset( $params['icon'] ) ) {
			$data['icon'] = sanitize_text_field( $params['icon'] );
		}
		if ( isset( $params['color'] ) ) {
			$data['color'] = sanitize_hex_color( $params['color'] ) ?: '#6366f1';
		}
		if ( isset( $params['model_id'] ) ) {
			$data['model_id'] = sanitize_text_field( $params['model_id'] );
		}
		if ( isset( $params['is_active'] ) ) {
			$data['is_active'] = $params['is_active'] ? 1 : 0;
		}

		$wpdb->update( $this->custom_assistants_table, $data, [ 'id' => $id ] );

		return $this->success( null, __( 'Assistant updated successfully.', 'saman-seo' ) );
	}

	/**
	 * Delete a custom assistant.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function delete_custom_assistant( $request ) {
		global $wpdb;

		$id       = intval( $request->get_param( 'id' ) );
		$existing = $this->get_custom_assistant_by_id( $id );

		if ( ! $existing ) {
			return $this->error( __( 'Assistant not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$wpdb->delete( $this->custom_assistants_table, [ 'id' => $id ] );

		return $this->success( null, __( 'Assistant deleted successfully.', 'saman-seo' ) );
	}

	// =========================================================================
	// USAGE STATS
	// =========================================================================

	/**
	 * Get overall usage stats.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_usage_stats( $request ) {
		global $wpdb;

		$this->maybe_create_usage_table();

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$this->usage_table
		) );

		if ( ! $table_exists ) {
			return $this->success( [
				'total_messages' => 0,
				'today'          => 0,
				'this_week'      => 0,
				'this_month'     => 0,
				'by_assistant'   => [],
			] );
		}

		$total      = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->usage_table}" );
		$today      = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->usage_table} WHERE DATE(created_at) = %s",
			current_time( 'Y-m-d' )
		) );
		$this_week  = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->usage_table} WHERE created_at >= %s",
			gmdate( 'Y-m-d', strtotime( '-7 days' ) )
		) );
		$this_month = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->usage_table} WHERE created_at >= %s",
			gmdate( 'Y-m-01' )
		) );

		$by_assistant = $wpdb->get_results(
			"SELECT assistant_id, COUNT(*) as count FROM {$this->usage_table} GROUP BY assistant_id ORDER BY count DESC",
			ARRAY_A
		);

		return $this->success( [
			'total_messages' => intval( $total ),
			'today'          => intval( $today ),
			'this_week'      => intval( $this_week ),
			'this_month'     => intval( $this_month ),
			'by_assistant'   => $by_assistant,
		] );
	}

	/**
	 * Track assistant usage.
	 *
	 * @param string $assistant_id Assistant ID.
	 * @param int    $tokens_used  Estimated tokens used.
	 */
	private function track_usage( $assistant_id, $tokens_used = 0 ) {
		global $wpdb;

		$this->maybe_create_usage_table();

		$wpdb->insert( $this->usage_table, [
			'assistant_id' => $assistant_id,
			'user_id'      => get_current_user_id(),
			'tokens_used'  => $tokens_used,
			'created_at'   => current_time( 'mysql' ),
		] );
	}

	/**
	 * Get usage count for an assistant.
	 *
	 * @param string $assistant_id Assistant ID.
	 * @return int
	 */
	private function get_assistant_usage_count( $assistant_id ) {
		global $wpdb;

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$this->usage_table
		) );

		if ( ! $table_exists ) {
			return 0;
		}

		return intval( $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->usage_table} WHERE assistant_id = %s",
			$assistant_id
		) ) );
	}

	// =========================================================================
	// HELPER METHODS
	// =========================================================================

	/**
	 * Get custom assistants list.
	 *
	 * @return array
	 */
	private function get_custom_assistants_list() {
		global $wpdb;

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$this->custom_assistants_table
		) );

		if ( ! $table_exists ) {
			return [];
		}

		return $wpdb->get_results(
			"SELECT * FROM {$this->custom_assistants_table} ORDER BY created_at DESC",
			ARRAY_A
		) ?? [];
	}

	/**
	 * Get custom assistant by ID.
	 *
	 * @param int $id Assistant ID.
	 * @return array|null
	 */
	private function get_custom_assistant_by_id( $id ) {
		global $wpdb;

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$this->custom_assistants_table
		) );

		if ( ! $table_exists ) {
			return null;
		}

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->custom_assistants_table} WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Create custom assistants table.
	 */
	private function maybe_create_assistants_table() {
		global $wpdb;

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$this->custom_assistants_table
		) );

		if ( $table_exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->custom_assistants_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            system_prompt longtext NOT NULL,
            initial_message text,
            suggested_prompts longtext,
            icon varchar(50) DEFAULT 'ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸Ãƒâ€šÃ‚Â¤ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“',
            color varchar(20) DEFAULT '#6366f1',
            model_id varchar(255) DEFAULT '',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY is_active (is_active)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create usage tracking table.
	 */
	private function maybe_create_usage_table() {
		global $wpdb;

		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$this->usage_table
		) );

		if ( $table_exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->usage_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            assistant_id varchar(100) NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            tokens_used int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY assistant_id (assistant_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
