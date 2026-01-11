<?php
/**
 * AI REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for AI settings, custom models, and generation.
 */
class Ai_Controller extends REST_Controller {

    /**
     * Custom models table name.
     *
     * @var string
     */
    private $custom_models_table;

    /**
     * AI setting keys.
     *
     * @var array
     */
    private $ai_settings = [
        'wpseopilot_openai_api_key',
        'wpseopilot_ai_model',
        'wpseopilot_ai_prompt_system',
        'wpseopilot_ai_prompt_title',
        'wpseopilot_ai_prompt_description',
        'wpseopilot_ai_active_provider',
    ];

    /**
     * Default values for AI settings.
     *
     * @var array
     */
    private $defaults = [
        'wpseopilot_openai_api_key'         => '',
        'wpseopilot_ai_model'               => 'gpt-4o-mini',
        'wpseopilot_ai_prompt_system'       => 'You are an SEO assistant generating concise metadata. Respond with plain text only.',
        'wpseopilot_ai_prompt_title'        => 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.',
        'wpseopilot_ai_prompt_description'  => 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.',
        'wpseopilot_ai_active_provider'     => 'openai',
    ];

    /**
     * Built-in OpenAI models.
     *
     * @var array
     */
    private $builtin_models = [
        'gpt-4o-mini'   => 'GPT-4o mini (fast, affordable)',
        'gpt-4o'        => 'GPT-4o (highest quality)',
        'gpt-4.1-mini'  => 'GPT-4.1 mini',
        'gpt-4.1'       => 'GPT-4.1',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
    ];

    /**
     * Supported providers with their configurations.
     *
     * @var array
     */
    private $providers = [
        'openai' => [
            'name'          => 'OpenAI',
            'api_url'       => 'https://api.openai.com/v1/chat/completions',
            'auth_type'     => 'bearer',
            'key_prefix'    => 'sk-',
            'supports_test' => true,
        ],
        'anthropic' => [
            'name'          => 'Anthropic',
            'api_url'       => 'https://api.anthropic.com/v1/messages',
            'auth_type'     => 'x-api-key',
            'key_prefix'    => 'sk-ant-',
            'supports_test' => true,
        ],
        'google' => [
            'name'          => 'Google AI',
            'api_url'       => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
            'auth_type'     => 'query_key',
            'key_prefix'    => '',
            'supports_test' => true,
        ],
        'openai_compatible' => [
            'name'          => 'OpenAI Compatible',
            'api_url'       => '', // User provides
            'auth_type'     => 'bearer',
            'key_prefix'    => '',
            'supports_test' => true,
        ],
        'lmstudio' => [
            'name'          => 'LM Studio',
            'api_url'       => 'http://localhost:1234/v1/chat/completions',
            'auth_type'     => 'none',
            'key_prefix'    => '',
            'supports_test' => true,
        ],
        'ollama' => [
            'name'          => 'Ollama',
            'api_url'       => 'http://localhost:11434/api/chat',
            'auth_type'     => 'none',
            'key_prefix'    => '',
            'supports_test' => true,
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->custom_models_table = $wpdb->prefix . 'wpseopilot_custom_models';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        // === Existing Settings Routes ===
        register_rest_route( $this->namespace, '/ai/settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/models', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_models' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/reset', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'reset_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/generate', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'generate' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_status' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // === Custom Models Routes ===
        register_rest_route( $this->namespace, '/ai/custom-models', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_custom_models' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_custom_model' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/custom-models/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_custom_model' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_custom_model' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_custom_model' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/custom-models/(?P<id>\d+)/test', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'test_custom_model' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // === Providers Routes ===
        register_rest_route( $this->namespace, '/ai/providers', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_providers' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // === Models Database (models.dev) Routes ===
        register_rest_route( $this->namespace, '/ai/models-database', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_models_database' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/models-database/sync', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'sync_models_database' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/models-database/search', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'search_models_database' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    // =========================================================================
    // EXISTING SETTINGS METHODS
    // =========================================================================

    /**
     * Get all AI settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        $settings = [];

        foreach ( $this->ai_settings as $key ) {
            $short_key = str_replace( 'wpseopilot_', '', $key );
            $value = get_option( $key );

            if ( false === $value ) {
                $value = isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : '';
            }

            if ( 'openai_api_key' === $short_key && ! empty( $value ) ) {
                $settings['api_key_configured'] = true;
                $settings[ $short_key ] = $this->mask_api_key( $value );
            } else {
                $settings[ $short_key ] = $value;
            }
        }

        return $this->success( $settings );
    }

    /**
     * Update AI settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_settings( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        foreach ( $params as $key => $value ) {
            $option_key = 'wpseopilot_' . $key;

            if ( ! in_array( $option_key, $this->ai_settings, true ) ) {
                continue;
            }

            if ( 'openai_api_key' === $key ) {
                if ( strpos( $value, '••••' ) !== false ) {
                    continue;
                }
                $value = sanitize_text_field( $value );
            } elseif ( 'ai_model' === $key ) {
                $value = sanitize_text_field( $value );
            } elseif ( 'ai_active_provider' === $key ) {
                $value = sanitize_text_field( $value );
            } else {
                $value = sanitize_textarea_field( $value );
            }

            update_option( $option_key, $value );
        }

        return $this->success( null, __( 'AI settings saved successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Get available AI models (built-in + custom).
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_models( $request ) {
        $models = [];

        // Add built-in models
        foreach ( $this->builtin_models as $value => $label ) {
            $models[] = [
                'value'    => $value,
                'label'    => $label,
                'provider' => 'openai',
                'builtin'  => true,
            ];
        }

        // Add custom models
        $custom_models = $this->get_custom_models_list();
        foreach ( $custom_models as $model ) {
            if ( $model['is_active'] ) {
                $models[] = [
                    'value'    => 'custom_' . $model['id'],
                    'label'    => $model['name'] . ' (' . $model['provider'] . ')',
                    'provider' => $model['provider'],
                    'builtin'  => false,
                    'custom_id'=> $model['id'],
                ];
            }
        }

        return $this->success( $models );
    }

    /**
     * Reset AI settings to defaults.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function reset_settings( $request ) {
        $reset_keys = [
            'wpseopilot_ai_model',
            'wpseopilot_ai_prompt_system',
            'wpseopilot_ai_prompt_title',
            'wpseopilot_ai_prompt_description',
        ];

        foreach ( $reset_keys as $key ) {
            if ( isset( $this->defaults[ $key ] ) ) {
                update_option( $key, $this->defaults[ $key ] );
            }
        }

        $settings = [];
        foreach ( $reset_keys as $key ) {
            $short_key = str_replace( 'wpseopilot_', '', $key );
            $settings[ $short_key ] = $this->defaults[ $key ];
        }

        return $this->success( $settings, __( 'AI settings restored to defaults.', 'wp-seo-pilot' ) );
    }

    /**
     * Test AI generation.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function generate( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $content = isset( $params['content'] ) ? sanitize_textarea_field( $params['content'] ) : '';
        $type = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'both';
        $model_id = isset( $params['model'] ) ? sanitize_text_field( $params['model'] ) : null;

        if ( empty( $content ) ) {
            return $this->error( __( 'Content is required for AI generation.', 'wp-seo-pilot' ), 'missing_content', 400 );
        }

        // Get settings
        $model = $model_id ?? get_option( 'wpseopilot_ai_model', 'gpt-4o-mini' );
        $system_prompt = get_option( 'wpseopilot_ai_prompt_system', $this->defaults['wpseopilot_ai_prompt_system'] );
        $title_prompt = get_option( 'wpseopilot_ai_prompt_title', $this->defaults['wpseopilot_ai_prompt_title'] );
        $description_prompt = get_option( 'wpseopilot_ai_prompt_description', $this->defaults['wpseopilot_ai_prompt_description'] );

        // Check if it's a custom model
        if ( strpos( $model, 'custom_' ) === 0 ) {
            $custom_id = intval( str_replace( 'custom_', '', $model ) );
            return $this->generate_with_custom_model( $custom_id, $content, $type, $system_prompt, $title_prompt, $description_prompt );
        }

        // Use OpenAI
        $api_key = get_option( 'wpseopilot_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            return $this->error( __( 'OpenAI API key is not configured.', 'wp-seo-pilot' ), 'no_api_key', 400 );
        }

        $results = [];

        if ( 'title' === $type || 'both' === $type ) {
            $title_result = $this->call_openai( $api_key, $model, $system_prompt, $title_prompt, $content, 60 );
            if ( is_wp_error( $title_result ) ) {
                return $this->error( $title_result->get_error_message(), 'api_error', 500 );
            }
            $results['title'] = $title_result;
        }

        if ( 'description' === $type || 'both' === $type ) {
            $desc_result = $this->call_openai( $api_key, $model, $system_prompt, $description_prompt, $content, 120 );
            if ( is_wp_error( $desc_result ) ) {
                return $this->error( $desc_result->get_error_message(), 'api_error', 500 );
            }
            $results['description'] = $desc_result;
        }

        return $this->success( $results, __( 'AI generation completed.', 'wp-seo-pilot' ) );
    }

    /**
     * Get API status.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_status( $request ) {
        $api_key = get_option( 'wpseopilot_openai_api_key', '' );
        $custom_models_count = count( $this->get_custom_models_list() );

        if ( empty( $api_key ) && $custom_models_count === 0 ) {
            return $this->success( [
                'configured'          => false,
                'status'              => 'not_configured',
                'message'             => __( 'No API configured', 'wp-seo-pilot' ),
                'custom_models_count' => 0,
            ] );
        }

        $is_valid_format = ! empty( $api_key ) && strpos( $api_key, 'sk-' ) === 0 && strlen( $api_key ) > 20;

        return $this->success( [
            'configured'          => ! empty( $api_key ) || $custom_models_count > 0,
            'openai_configured'   => ! empty( $api_key ),
            'valid_format'        => $is_valid_format,
            'status'              => $is_valid_format || $custom_models_count > 0 ? 'configured' : 'partial',
            'message'             => $is_valid_format
                ? __( 'API configured', 'wp-seo-pilot' )
                : ( $custom_models_count > 0 ? __( 'Custom models available', 'wp-seo-pilot' ) : __( 'API key format may be invalid', 'wp-seo-pilot' ) ),
            'custom_models_count' => $custom_models_count,
        ] );
    }

    // =========================================================================
    // CUSTOM MODELS CRUD
    // =========================================================================

    /**
     * Get all custom models.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_custom_models( $request ) {
        $models = $this->get_custom_models_list();

        // Mask API keys
        foreach ( $models as &$model ) {
            if ( ! empty( $model['api_key'] ) ) {
                $model['api_key_configured'] = true;
                $model['api_key'] = $this->mask_api_key( $model['api_key'] );
            } else {
                $model['api_key_configured'] = false;
            }
        }

        return $this->success( $models );
    }

    /**
     * Get a single custom model.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_custom_model( $request ) {
        $id = intval( $request->get_param( 'id' ) );
        $model = $this->get_custom_model_by_id( $id );

        if ( ! $model ) {
            return $this->error( __( 'Model not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        // Mask API key
        if ( ! empty( $model['api_key'] ) ) {
            $model['api_key_configured'] = true;
            $model['api_key'] = $this->mask_api_key( $model['api_key'] );
        }

        return $this->success( $model );
    }

    /**
     * Create a custom model.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function create_custom_model( $request ) {
        global $wpdb;

        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        // Validate required fields
        if ( empty( $params['name'] ) ) {
            return $this->error( __( 'Model name is required.', 'wp-seo-pilot' ), 'missing_name', 400 );
        }

        if ( empty( $params['provider'] ) ) {
            return $this->error( __( 'Provider is required.', 'wp-seo-pilot' ), 'missing_provider', 400 );
        }

        if ( empty( $params['model_id'] ) ) {
            return $this->error( __( 'Model ID is required.', 'wp-seo-pilot' ), 'missing_model_id', 400 );
        }

        // Ensure table exists
        $this->maybe_create_table();

        $data = [
            'name'        => sanitize_text_field( $params['name'] ),
            'provider'    => sanitize_text_field( $params['provider'] ),
            'model_id'    => sanitize_text_field( $params['model_id'] ),
            'api_url'     => isset( $params['api_url'] ) ? esc_url_raw( $params['api_url'], [ 'http', 'https' ] ) : '',
            'api_key'     => isset( $params['api_key'] ) ? sanitize_text_field( $params['api_key'] ) : '',
            'temperature' => isset( $params['temperature'] ) ? floatval( $params['temperature'] ) : 0.3,
            'max_tokens'  => isset( $params['max_tokens'] ) ? intval( $params['max_tokens'] ) : 1000,
            'is_active'   => isset( $params['is_active'] ) ? ( $params['is_active'] ? 1 : 0 ) : 1,
            'extra_config'=> isset( $params['extra_config'] ) ? wp_json_encode( $params['extra_config'] ) : null,
            'created_at'  => current_time( 'mysql' ),
            'updated_at'  => current_time( 'mysql' ),
        ];

        $format = [
            '%s', // name
            '%s', // provider
            '%s', // model_id
            '%s', // api_url
            '%s', // api_key
            '%f', // temperature
            '%d', // max_tokens
            '%d', // is_active
            '%s', // extra_config
            '%s', // created_at
            '%s', // updated_at
        ];

        $result = $wpdb->insert( $this->custom_models_table, $data, $format );

        if ( false === $result ) {
            $db_error = $wpdb->last_error;
            return $this->error(
                sprintf( __( 'Failed to create model: %s', 'wp-seo-pilot' ), $db_error ),
                'db_error',
                500
            );
        }

        $model_id = $wpdb->insert_id;

        return $this->success( [ 'id' => $model_id ], __( 'Custom model created successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Update a custom model.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_custom_model( $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $existing = $this->get_custom_model_by_id( $id );

        if ( ! $existing ) {
            return $this->error( __( 'Model not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $data = [ 'updated_at' => current_time( 'mysql' ) ];

        if ( isset( $params['name'] ) ) {
            $data['name'] = sanitize_text_field( $params['name'] );
        }
        if ( isset( $params['provider'] ) ) {
            $data['provider'] = sanitize_text_field( $params['provider'] );
        }
        if ( isset( $params['model_id'] ) ) {
            $data['model_id'] = sanitize_text_field( $params['model_id'] );
        }
        if ( isset( $params['api_url'] ) ) {
            $data['api_url'] = esc_url_raw( $params['api_url'] );
        }
        if ( isset( $params['api_key'] ) && strpos( $params['api_key'], '••••' ) === false ) {
            $data['api_key'] = sanitize_text_field( $params['api_key'] );
        }
        if ( isset( $params['temperature'] ) ) {
            $data['temperature'] = floatval( $params['temperature'] );
        }
        if ( isset( $params['max_tokens'] ) ) {
            $data['max_tokens'] = intval( $params['max_tokens'] );
        }
        if ( isset( $params['is_active'] ) ) {
            $data['is_active'] = intval( $params['is_active'] );
        }
        if ( isset( $params['extra_config'] ) ) {
            $data['extra_config'] = wp_json_encode( $params['extra_config'] );
        }

        $wpdb->update( $this->custom_models_table, $data, [ 'id' => $id ] );

        return $this->success( null, __( 'Custom model updated successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Delete a custom model.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function delete_custom_model( $request ) {
        global $wpdb;

        $id = intval( $request->get_param( 'id' ) );
        $existing = $this->get_custom_model_by_id( $id );

        if ( ! $existing ) {
            return $this->error( __( 'Model not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $wpdb->delete( $this->custom_models_table, [ 'id' => $id ] );

        return $this->success( null, __( 'Custom model deleted successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Test a custom model connection.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function test_custom_model( $request ) {
        $id = intval( $request->get_param( 'id' ) );
        $model = $this->get_custom_model_by_id( $id );

        if ( ! $model ) {
            return $this->error( __( 'Model not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $test_prompt = 'Say "Hello, connection successful!" in exactly those words.';
        $result = $this->call_provider_api( $model, 'You are a helpful assistant.', $test_prompt, 50 );

        if ( is_wp_error( $result ) ) {
            return $this->success( [
                'success' => false,
                'message' => $result->get_error_message(),
            ] );
        }

        return $this->success( [
            'success'  => true,
            'message'  => __( 'Connection successful!', 'wp-seo-pilot' ),
            'response' => $result,
        ] );
    }

    // =========================================================================
    // PROVIDERS
    // =========================================================================

    /**
     * Get available providers.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_providers( $request ) {
        $providers = [];

        foreach ( $this->providers as $id => $config ) {
            $providers[] = [
                'id'           => $id,
                'name'         => $config['name'],
                'api_url'      => $config['api_url'],
                'auth_type'    => $config['auth_type'],
                'key_prefix'   => $config['key_prefix'],
                'needs_url'    => $id === 'openai_compatible',
                'is_local'     => in_array( $id, [ 'lmstudio', 'ollama' ], true ),
            ];
        }

        return $this->success( $providers );
    }

    // =========================================================================
    // MODELS DATABASE (models.dev)
    // =========================================================================

    /**
     * Get cached models database.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_models_database( $request ) {
        $cache = get_option( 'wpseopilot_models_database_cache', null );
        $last_sync = get_option( 'wpseopilot_models_database_last_sync', 0 );

        // Auto-sync if cache is empty or older than 2 weeks
        $two_weeks = 14 * DAY_IN_SECONDS;
        if ( empty( $cache ) || ( time() - $last_sync ) > $two_weeks ) {
            $sync_result = $this->sync_models_from_api();
            if ( ! is_wp_error( $sync_result ) ) {
                $cache = $sync_result;
            }
        }

        return $this->success( [
            'models'         => $cache ?? [],
            'last_sync'      => $last_sync ? date( 'Y-m-d H:i:s', $last_sync ) : null,
            'next_sync'      => $last_sync ? date( 'Y-m-d H:i:s', $last_sync + $two_weeks ) : null,
            'models_count'   => is_array( $cache ) ? count( $cache ) : 0,
        ] );
    }

    /**
     * Force sync models database from models.dev.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function sync_models_database( $request ) {
        $result = $this->sync_models_from_api();

        if ( is_wp_error( $result ) ) {
            return $this->error( $result->get_error_message(), 'sync_failed', 500 );
        }

        return $this->success( [
            'models'       => $result,
            'models_count' => count( $result ),
            'last_sync'    => date( 'Y-m-d H:i:s' ),
        ], __( 'Models database synced successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Search models database.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function search_models_database( $request ) {
        $query = sanitize_text_field( $request->get_param( 'q' ) ?? '' );
        $provider = sanitize_text_field( $request->get_param( 'provider' ) ?? '' );
        $limit = intval( $request->get_param( 'limit' ) ?? 50 );

        $cache = get_option( 'wpseopilot_models_database_cache', [] );

        if ( empty( $cache ) ) {
            return $this->success( [] );
        }

        $results = [];

        foreach ( $cache as $model_id => $model ) {
            // Filter by provider
            if ( ! empty( $provider ) && isset( $model['provider'] ) ) {
                if ( strtolower( $model['provider'] ) !== strtolower( $provider ) ) {
                    continue;
                }
            }

            // Filter by query
            if ( ! empty( $query ) ) {
                $searchable = strtolower( $model_id . ' ' . ( $model['name'] ?? '' ) . ' ' . ( $model['provider'] ?? '' ) );
                if ( strpos( $searchable, strtolower( $query ) ) === false ) {
                    continue;
                }
            }

            $results[ $model_id ] = $model;

            if ( count( $results ) >= $limit ) {
                break;
            }
        }

        return $this->success( $results );
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get custom models list from database.
     *
     * @return array
     */
    private function get_custom_models_list() {
        global $wpdb;

        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->custom_models_table
        ) );

        if ( ! $table_exists ) {
            return [];
        }

        $results = $wpdb->get_results(
            "SELECT * FROM {$this->custom_models_table} ORDER BY created_at DESC",
            ARRAY_A
        );

        return $results ?? [];
    }

    /**
     * Get a single custom model by ID.
     *
     * @param int $id Model ID.
     * @return array|null
     */
    private function get_custom_model_by_id( $id ) {
        global $wpdb;

        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->custom_models_table
        ) );

        if ( ! $table_exists ) {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->custom_models_table} WHERE id = %d", $id ),
            ARRAY_A
        );
    }

    /**
     * Maybe create custom models table.
     */
    private function maybe_create_table() {
        global $wpdb;

        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->custom_models_table
        ) );

        if ( $table_exists ) {
            return true;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->custom_models_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            provider varchar(100) NOT NULL,
            model_id varchar(255) NOT NULL,
            api_url varchar(500) NOT NULL DEFAULT '',
            api_key varchar(500) NOT NULL DEFAULT '',
            temperature decimal(3,2) NOT NULL DEFAULT 0.30,
            max_tokens int(11) NOT NULL DEFAULT 1000,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            extra_config longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY provider (provider),
            KEY is_active (is_active)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $result = dbDelta( $sql );

        // Log if there were any errors
        if ( ! empty( $wpdb->last_error ) ) {
            error_log( 'WP SEO Pilot: Table creation error: ' . $wpdb->last_error );
        }

        return true;
    }

    /**
     * Sync models from models.dev API.
     *
     * @return array|\WP_Error
     */
    private function sync_models_from_api() {
        $response = wp_remote_get( 'https://models.dev/api.json', [
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            return new \WP_Error( 'api_error', __( 'Failed to fetch models from models.dev', 'wp-seo-pilot' ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $body ) ) {
            return new \WP_Error( 'invalid_response', __( 'Invalid response from models.dev', 'wp-seo-pilot' ) );
        }

        // Convert object format to array format
        // models.dev returns { "model-id": {...}, ... } but we need [{...}, {...}, ...]
        $models_array = [];
        foreach ( $body as $model_id => $model_data ) {
            if ( is_array( $model_data ) ) {
                // Ensure each model has its ID
                if ( ! isset( $model_data['id'] ) ) {
                    $model_data['id'] = $model_id;
                }
                $models_array[] = $model_data;
            }
        }

        // Store the cache as array
        update_option( 'wpseopilot_models_database_cache', $models_array, false );
        update_option( 'wpseopilot_models_database_last_sync', time() );

        return $models_array;
    }

    /**
     * Generate with a custom model.
     *
     * @param int    $custom_id          Custom model ID.
     * @param string $content            Content to analyze.
     * @param string $type               Generation type (title, description, both).
     * @param string $system_prompt      System prompt.
     * @param string $title_prompt       Title prompt.
     * @param string $description_prompt Description prompt.
     * @return \WP_REST_Response
     */
    private function generate_with_custom_model( $custom_id, $content, $type, $system_prompt, $title_prompt, $description_prompt ) {
        $model = $this->get_custom_model_by_id( $custom_id );

        if ( ! $model ) {
            return $this->error( __( 'Custom model not found.', 'wp-seo-pilot' ), 'model_not_found', 404 );
        }

        if ( ! $model['is_active'] ) {
            return $this->error( __( 'Custom model is not active.', 'wp-seo-pilot' ), 'model_inactive', 400 );
        }

        $results = [];

        if ( 'title' === $type || 'both' === $type ) {
            $prompt = $title_prompt . "\n\nContent:\n" . $content;
            $result = $this->call_provider_api( $model, $system_prompt, $prompt, 60 );
            if ( is_wp_error( $result ) ) {
                return $this->error( $result->get_error_message(), 'api_error', 500 );
            }
            $results['title'] = $result;
        }

        if ( 'description' === $type || 'both' === $type ) {
            $prompt = $description_prompt . "\n\nContent:\n" . $content;
            $result = $this->call_provider_api( $model, $system_prompt, $prompt, 120 );
            if ( is_wp_error( $result ) ) {
                return $this->error( $result->get_error_message(), 'api_error', 500 );
            }
            $results['description'] = $result;
        }

        return $this->success( $results, __( 'AI generation completed.', 'wp-seo-pilot' ) );
    }

    /**
     * Call provider API based on model configuration.
     *
     * @param array  $model      Model configuration.
     * @param string $system     System prompt.
     * @param string $prompt     User prompt.
     * @param int    $max_tokens Max tokens.
     * @return string|\WP_Error
     */
    private function call_provider_api( $model, $system, $prompt, $max_tokens = 100 ) {
        $provider = $model['provider'];
        $api_url = ! empty( $model['api_url'] ) ? $model['api_url'] : ( $this->providers[ $provider ]['api_url'] ?? '' );
        $api_key = $model['api_key'] ?? '';
        $model_id = $model['model_id'];
        $temperature = floatval( $model['temperature'] ?? 0.3 );

        switch ( $provider ) {
            case 'openai':
            case 'openai_compatible':
            case 'lmstudio':
                return $this->call_openai_compatible( $api_url, $api_key, $model_id, $system, $prompt, $max_tokens, $temperature );

            case 'anthropic':
                return $this->call_anthropic( $api_url, $api_key, $model_id, $system, $prompt, $max_tokens, $temperature );

            case 'google':
                return $this->call_google( $api_url, $api_key, $model_id, $system, $prompt, $max_tokens, $temperature );

            case 'ollama':
                return $this->call_ollama( $api_url, $model_id, $system, $prompt, $max_tokens, $temperature );

            default:
                return new \WP_Error( 'unsupported_provider', __( 'Unsupported provider', 'wp-seo-pilot' ) );
        }
    }

    /**
     * Call OpenAI or OpenAI-compatible API.
     */
    private function call_openai_compatible( $api_url, $api_key, $model, $system, $prompt, $max_tokens, $temperature ) {
        $headers = [ 'Content-Type' => 'application/json' ];

        if ( ! empty( $api_key ) ) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        $body = [
            'model'       => $model,
            'messages'    => [
                [ 'role' => 'system', 'content' => $system ],
                [ 'role' => 'user', 'content' => $prompt ],
            ],
            'max_tokens'  => $max_tokens,
            'temperature' => $temperature,
        ];

        $response = wp_remote_post( $api_url, [
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['choices'][0]['message']['content'] ?? '' );
    }

    /**
     * Call OpenAI API (original method for backwards compatibility).
     */
    private function call_openai( $api_key, $model, $system, $instruction, $content, $max_tokens = 60 ) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $user_message = $instruction . "\n\nContent:\n" . $content;

        return $this->call_openai_compatible( $url, $api_key, $model, $system, $user_message, $max_tokens, 0.3 );
    }

    /**
     * Call Anthropic API.
     */
    private function call_anthropic( $api_url, $api_key, $model, $system, $prompt, $max_tokens, $temperature ) {
        $body = [
            'model'      => $model,
            'max_tokens' => $max_tokens,
            'system'     => $system,
            'messages'   => [
                [ 'role' => 'user', 'content' => $prompt ],
            ],
        ];

        $response = wp_remote_post( $api_url, [
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'Anthropic API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['content'][0]['text'] ?? '' );
    }

    /**
     * Call Google AI API.
     */
    private function call_google( $api_url, $api_key, $model, $system, $prompt, $max_tokens, $temperature ) {
        $url = str_replace( '{model}', $model, $api_url ) . '?key=' . $api_key;

        $body = [
            'contents' => [
                [
                    'parts' => [
                        [ 'text' => $system . "\n\n" . $prompt ],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $max_tokens,
                'temperature'     => $temperature,
            ],
        ];

        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'Google AI API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['candidates'][0]['content']['parts'][0]['text'] ?? '' );
    }

    /**
     * Call Ollama API.
     */
    private function call_ollama( $api_url, $model, $system, $prompt, $max_tokens, $temperature ) {
        $body = [
            'model'   => $model,
            'messages'=> [
                [ 'role' => 'system', 'content' => $system ],
                [ 'role' => 'user', 'content' => $prompt ],
            ],
            'stream'  => false,
            'options' => [
                'temperature'  => $temperature,
                'num_predict'  => $max_tokens,
            ],
        ];

        $response = wp_remote_post( $api_url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 120, // Ollama can be slow
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error'] ?? __( 'Ollama API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['message']['content'] ?? '' );
    }

    /**
     * Mask API key for display.
     *
     * @param string $key API key.
     * @return string
     */
    private function mask_api_key( $key ) {
        if ( strlen( $key ) <= 8 ) {
            return str_repeat( '•', strlen( $key ) );
        }

        return substr( $key, 0, 4 ) . str_repeat( '•', strlen( $key ) - 8 ) . substr( $key, -4 );
    }
}
