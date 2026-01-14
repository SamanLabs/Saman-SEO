<?php
/**
 * Mobile Test REST API Controller
 *
 * Analyzes pages for mobile-friendliness.
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mobile_Test_Controller class.
 */
class Mobile_Test_Controller extends REST_Controller {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->namespace = 'wpseopilot/v2';
        $this->rest_base = 'mobile-test';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        // Analyze URL
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/analyze',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'analyze_url' ],
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

        // Get recent tests
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/recent',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_recent' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ]
        );
    }

    /**
     * Analyze URL for mobile friendliness.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function analyze_url( $request ) {
        $url = $request->get_param( 'url' );

        if ( empty( $url ) ) {
            return $this->error( 'Please provide a valid URL' );
        }

        // Fetch the page content
        $response = wp_remote_get( $url, [
            'timeout'    => 15,
            'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
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

        // Run mobile checks
        $checks = [];
        $issues = [];
        $score  = 100;

        // Check 1: Viewport meta tag
        $has_viewport = $this->check_viewport( $body );
        $checks[]     = [
            'name'   => 'Viewport Meta Tag',
            'status' => $has_viewport ? 'pass' : 'fail',
            'detail' => $has_viewport ? 'Viewport is properly configured' : 'Missing viewport meta tag',
        ];
        if ( ! $has_viewport ) {
            $score   -= 25;
            $issues[] = [
                'severity'    => 'critical',
                'title'       => 'Missing Viewport Meta Tag',
                'description' => 'The page does not have a viewport meta tag, which is essential for mobile rendering.',
                'fix'         => 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> to your <head>',
            ];
        }

        // Check 2: Responsive design indicators
        $has_responsive = $this->check_responsive( $body );
        $checks[]       = [
            'name'   => 'Responsive Design',
            'status' => $has_responsive ? 'pass' : 'warning',
            'detail' => $has_responsive ? 'Uses responsive CSS' : 'Limited responsive indicators found',
        ];
        if ( ! $has_responsive ) {
            $score   -= 10;
            $issues[] = [
                'severity'    => 'warning',
                'title'       => 'Limited Responsive CSS',
                'description' => 'The page may not be using responsive design techniques effectively.',
                'fix'         => 'Use CSS media queries and flexible layouts for better mobile experience',
            ];
        }

        // Check 3: Touch target sizes (approximate check)
        $small_links = $this->check_touch_targets( $body );
        $checks[]    = [
            'name'   => 'Touch Targets',
            'status' => $small_links === 0 ? 'pass' : ( $small_links < 5 ? 'warning' : 'fail' ),
            'detail' => $small_links === 0 ? 'Touch targets appear adequate' : "{$small_links} potentially small touch targets",
        ];
        if ( $small_links >= 5 ) {
            $score   -= 15;
            $issues[] = [
                'severity'    => 'warning',
                'title'       => 'Small Touch Targets',
                'description' => 'Some links or buttons may be too small for easy mobile tapping.',
                'fix'         => 'Ensure interactive elements are at least 44x44 pixels',
            ];
        }

        // Check 4: Font sizes
        $font_issues = $this->check_font_sizes( $body );
        $checks[]    = [
            'name'   => 'Font Sizes',
            'status' => $font_issues === 0 ? 'pass' : 'warning',
            'detail' => $font_issues === 0 ? 'Font sizes appear readable' : 'Some text may be too small',
        ];
        if ( $font_issues > 0 ) {
            $score   -= 10;
            $issues[] = [
                'severity'    => 'info',
                'title'       => 'Small Font Sizes Detected',
                'description' => 'Some text elements use very small font sizes that may be hard to read on mobile.',
                'fix'         => 'Use a minimum font size of 16px for body text',
            ];
        }

        // Check 5: Horizontal scrolling
        $fixed_widths = $this->check_fixed_widths( $body );
        $checks[]     = [
            'name'   => 'Content Width',
            'status' => $fixed_widths === 0 ? 'pass' : 'warning',
            'detail' => $fixed_widths === 0 ? 'No fixed-width issues detected' : 'Fixed-width elements may cause scrolling',
        ];
        if ( $fixed_widths > 3 ) {
            $score   -= 10;
            $issues[] = [
                'severity'    => 'warning',
                'title'       => 'Fixed-Width Elements',
                'description' => 'Large fixed-width elements may cause horizontal scrolling on mobile devices.',
                'fix'         => 'Use percentage widths or max-width instead of fixed pixel widths',
            ];
        }

        // Check 6: Mobile-unfriendly plugins
        $has_flash = $this->check_for_flash( $body );
        $checks[]  = [
            'name'   => 'Plugin Content',
            'status' => $has_flash ? 'fail' : 'pass',
            'detail' => $has_flash ? 'Flash or incompatible plugins detected' : 'No incompatible plugins found',
        ];
        if ( $has_flash ) {
            $score   -= 20;
            $issues[] = [
                'severity'    => 'critical',
                'title'       => 'Incompatible Plugins',
                'description' => 'The page uses Flash or other plugins not supported on mobile devices.',
                'fix'         => 'Replace Flash content with HTML5 alternatives',
            ];
        }

        // Check 7: Text content ratio
        $text_ratio = $this->check_text_ratio( $body );
        $checks[]   = [
            'name'   => 'Content Readability',
            'status' => $text_ratio > 0.1 ? 'pass' : 'warning',
            'detail' => $text_ratio > 0.1 ? 'Good text-to-HTML ratio' : 'Low text content detected',
        ];

        // Ensure score doesn't go below 0
        $score = max( 0, $score );

        // Determine overall status
        $status = 'pass';
        if ( $score < 90 ) {
            $status = 'warning';
        }
        if ( $score < 70 ) {
            $status = 'fail';
        }

        // Save to recent tests
        $this->save_recent_test( $url, $score );

        return $this->success( [
            'url'    => $url,
            'score'  => $score,
            'status' => $status,
            'checks' => $checks,
            'issues' => $issues,
        ] );
    }

    /**
     * Get recent tests.
     *
     * @return \WP_REST_Response
     */
    public function get_recent() {
        $recent = get_option( 'wpseopilot_mobile_tests', [] );
        return $this->success( $recent );
    }

    /**
     * Check for viewport meta tag.
     *
     * @param string $html HTML content.
     * @return bool
     */
    private function check_viewport( $html ) {
        return (bool) preg_match( '/<meta[^>]*name=["\']viewport["\'][^>]*>/i', $html );
    }

    /**
     * Check for responsive CSS indicators.
     *
     * @param string $html HTML content.
     * @return bool
     */
    private function check_responsive( $html ) {
        // Check for media queries
        $has_media_queries = (bool) preg_match( '/@media[^{]*\([^)]*width[^)]*\)/i', $html );

        // Check for bootstrap or other responsive frameworks
        $has_frameworks = (bool) preg_match( '/bootstrap|foundation|tailwind|bulma/i', $html );

        // Check for responsive images
        $has_responsive_images = (bool) preg_match( '/<img[^>]*(srcset|sizes|class=["\'][^"\']*responsive)/i', $html );

        return $has_media_queries || $has_frameworks || $has_responsive_images;
    }

    /**
     * Check for small touch targets.
     *
     * @param string $html HTML content.
     * @return int Number of potential issues.
     */
    private function check_touch_targets( $html ) {
        $count = 0;

        // Check for inline styles with very small dimensions
        if ( preg_match_all( '/style=["\'][^"\']*(?:width|height)\s*:\s*([0-9]+)(?:px)?/i', $html, $matches ) ) {
            foreach ( $matches[1] as $size ) {
                if ( (int) $size < 30 && (int) $size > 0 ) {
                    $count++;
                }
            }
        }

        return min( $count, 20 ); // Cap at 20
    }

    /**
     * Check for small font sizes.
     *
     * @param string $html HTML content.
     * @return int Number of issues.
     */
    private function check_font_sizes( $html ) {
        $count = 0;

        // Check for inline styles with small fonts
        if ( preg_match_all( '/style=["\'][^"\']*font-size\s*:\s*([0-9]+)(?:px)?/i', $html, $matches ) ) {
            foreach ( $matches[1] as $size ) {
                if ( (int) $size < 12 && (int) $size > 0 ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check for fixed-width elements.
     *
     * @param string $html HTML content.
     * @return int Number of issues.
     */
    private function check_fixed_widths( $html ) {
        $count = 0;

        // Check for large fixed widths
        if ( preg_match_all( '/(?:width|min-width)\s*:\s*([0-9]+)px/i', $html, $matches ) ) {
            foreach ( $matches[1] as $width ) {
                if ( (int) $width > 500 ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Check for Flash or incompatible plugins.
     *
     * @param string $html HTML content.
     * @return bool
     */
    private function check_for_flash( $html ) {
        return (bool) preg_match( '/<(?:object|embed)[^>]*(?:flash|swf|application\/x-shockwave)/i', $html );
    }

    /**
     * Check text-to-HTML ratio.
     *
     * @param string $html HTML content.
     * @return float Ratio.
     */
    private function check_text_ratio( $html ) {
        $text_length = strlen( wp_strip_all_tags( $html ) );
        $html_length = strlen( $html );

        return $html_length > 0 ? $text_length / $html_length : 0;
    }

    /**
     * Save recent test.
     *
     * @param string $url   URL tested.
     * @param int    $score Score.
     */
    private function save_recent_test( $url, $score ) {
        $recent = get_option( 'wpseopilot_mobile_tests', [] );

        // Remove existing entry for same URL
        $recent = array_filter( $recent, function ( $item ) use ( $url ) {
            return $item['url'] !== $url;
        } );

        // Add new entry
        array_unshift( $recent, [
            'url'   => $url,
            'score' => $score,
            'date'  => current_time( 'M j, Y g:i a' ),
        ] );

        // Keep only last 10
        $recent = array_slice( $recent, 0, 10 );

        update_option( 'wpseopilot_mobile_tests', $recent );
    }
}
