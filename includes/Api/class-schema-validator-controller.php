<?php
/**
 * Schema Validator REST API Controller
 *
 * Validates JSON-LD structured data from URLs.
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schema_Validator_Controller class.
 */
class Schema_Validator_Controller extends REST_Controller {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->namespace = 'wpseopilot/v2';
        $this->rest_base = 'schema-validator';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/validate',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'validate_url' ],
                'permission_callback' => [ $this, 'check_permission' ],
                'args'                => [
                    'url' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ],
                ],
            ]
        );
    }

    /**
     * Validate structured data from a URL.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function validate_url( $request ) {
        $url = $request->get_param( 'url' );

        if ( empty( $url ) ) {
            return $this->error( 'Please provide a valid URL' );
        }

        // Fetch the page content
        $response = wp_remote_get( $url, [
            'timeout'    => 15,
            'user-agent' => 'WP SEO Pilot Schema Validator/1.0',
            'sslverify'  => false,
        ] );

        if ( is_wp_error( $response ) ) {
            return $this->error( 'Failed to fetch URL: ' . $response->get_error_message() );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code !== 200 ) {
            return $this->error( "URL returned status code: {$status_code}" );
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return $this->error( 'Empty response from URL' );
        }

        // Extract JSON-LD scripts
        $schemas = $this->extract_jsonld( $body );

        // Validate each schema
        $validated_schemas = [];
        $valid_count       = 0;
        $warning_count     = 0;
        $error_count       = 0;

        foreach ( $schemas as $schema ) {
            $validation = $this->validate_schema( $schema );
            $validated_schemas[] = $validation;

            if ( ! empty( $validation['errors'] ) ) {
                $error_count += count( $validation['errors'] );
            } elseif ( ! empty( $validation['warnings'] ) ) {
                $warning_count += count( $validation['warnings'] );
            } else {
                $valid_count++;
            }
        }

        return $this->success( [
            'url'           => $url,
            'schemas'       => $validated_schemas,
            'valid_count'   => $valid_count,
            'warning_count' => $warning_count,
            'error_count'   => $error_count,
        ] );
    }

    /**
     * Extract JSON-LD scripts from HTML.
     *
     * @param string $html HTML content.
     * @return array Array of decoded JSON objects.
     */
    private function extract_jsonld( $html ) {
        $schemas = [];

        // Match JSON-LD script tags
        if ( preg_match_all( '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches ) ) {
            foreach ( $matches[1] as $json_string ) {
                $json_string = trim( $json_string );
                if ( empty( $json_string ) ) {
                    continue;
                }

                $decoded = json_decode( $json_string, true );
                if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
                    // Handle @graph arrays
                    if ( isset( $decoded['@graph'] ) && is_array( $decoded['@graph'] ) ) {
                        foreach ( $decoded['@graph'] as $item ) {
                            if ( is_array( $item ) ) {
                                $schemas[] = $item;
                            }
                        }
                    } else {
                        $schemas[] = $decoded;
                    }
                }
            }
        }

        return $schemas;
    }

    /**
     * Validate a single schema object.
     *
     * @param array $schema Schema data.
     * @return array Validation result.
     */
    private function validate_schema( $schema ) {
        $type       = $schema['@type'] ?? 'Unknown';
        $errors     = [];
        $warnings   = [];
        $properties = [];

        // Extract key properties for display
        $display_keys = [ 'name', 'headline', 'description', 'url', 'image', 'author', 'datePublished', 'price', 'availability' ];
        foreach ( $display_keys as $key ) {
            if ( isset( $schema[ $key ] ) ) {
                $properties[ $key ] = $schema[ $key ];
            }
        }

        // Common validations
        if ( ! isset( $schema['@type'] ) ) {
            $errors[] = 'Missing required @type property';
        }

        // Type-specific validations
        switch ( $type ) {
            case 'Article':
            case 'NewsArticle':
            case 'BlogPosting':
                if ( empty( $schema['headline'] ) ) {
                    $errors[] = 'Missing required "headline" property';
                }
                if ( empty( $schema['author'] ) ) {
                    $warnings[] = 'Missing recommended "author" property';
                }
                if ( empty( $schema['datePublished'] ) ) {
                    $warnings[] = 'Missing recommended "datePublished" property';
                }
                if ( empty( $schema['image'] ) ) {
                    $warnings[] = 'Missing recommended "image" property';
                }
                break;

            case 'Product':
                if ( empty( $schema['name'] ) ) {
                    $errors[] = 'Missing required "name" property';
                }
                if ( empty( $schema['offers'] ) ) {
                    $warnings[] = 'Missing recommended "offers" property for rich results';
                }
                if ( empty( $schema['image'] ) ) {
                    $warnings[] = 'Missing recommended "image" property';
                }
                break;

            case 'Organization':
            case 'LocalBusiness':
                if ( empty( $schema['name'] ) ) {
                    $errors[] = 'Missing required "name" property';
                }
                if ( empty( $schema['url'] ) ) {
                    $warnings[] = 'Missing recommended "url" property';
                }
                if ( $type === 'LocalBusiness' && empty( $schema['address'] ) ) {
                    $warnings[] = 'Missing recommended "address" property for LocalBusiness';
                }
                break;

            case 'WebSite':
                if ( empty( $schema['name'] ) ) {
                    $warnings[] = 'Missing recommended "name" property';
                }
                if ( empty( $schema['url'] ) ) {
                    $errors[] = 'Missing required "url" property';
                }
                break;

            case 'BreadcrumbList':
                if ( empty( $schema['itemListElement'] ) ) {
                    $errors[] = 'Missing required "itemListElement" property';
                }
                break;

            case 'FAQPage':
                if ( empty( $schema['mainEntity'] ) ) {
                    $errors[] = 'Missing required "mainEntity" property with questions';
                }
                break;

            case 'HowTo':
                if ( empty( $schema['name'] ) ) {
                    $errors[] = 'Missing required "name" property';
                }
                if ( empty( $schema['step'] ) ) {
                    $errors[] = 'Missing required "step" property';
                }
                break;

            case 'VideoObject':
                if ( empty( $schema['name'] ) ) {
                    $errors[] = 'Missing required "name" property';
                }
                if ( empty( $schema['uploadDate'] ) ) {
                    $errors[] = 'Missing required "uploadDate" property';
                }
                if ( empty( $schema['thumbnailUrl'] ) ) {
                    $warnings[] = 'Missing recommended "thumbnailUrl" property';
                }
                break;
        }

        // URL validation
        if ( ! empty( $schema['url'] ) && ! filter_var( $schema['url'], FILTER_VALIDATE_URL ) ) {
            $errors[] = 'Invalid URL format in "url" property';
        }

        // Image validation
        if ( ! empty( $schema['image'] ) ) {
            $image_url = is_array( $schema['image'] ) ? ( $schema['image']['url'] ?? $schema['image'][0] ?? '' ) : $schema['image'];
            if ( ! empty( $image_url ) && ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
                $warnings[] = 'Invalid URL format in "image" property';
            }
        }

        return [
            'type'       => $type,
            'name'       => $schema['name'] ?? $schema['headline'] ?? null,
            'errors'     => $errors,
            'warnings'   => $warnings,
            'properties' => $properties,
            'raw'        => $schema,
        ];
    }
}
