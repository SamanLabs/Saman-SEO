<?php
/**
 * Audit REST Controller
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Service\Post_Meta;
use function Saman\SEO\Helpers\generate_title_from_template;
use function Saman\SEO\Helpers\calculate_seo_score;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for SEO Audit functionality.
 */
class Audit_Controller extends REST_Controller {

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get audit results (cached or run new)
        register_rest_route( $this->namespace, '/audit', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_audit' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Run a new audit
        register_rest_route( $this->namespace, '/audit/run', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'run_audit' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'post_type' => [
                        'type'        => 'string',
                        'default'     => 'any',
                        'description' => 'Post type to audit',
                    ],
                    'limit' => [
                        'type'        => 'integer',
                        'default'     => 100,
                        'description' => 'Maximum posts to scan',
                    ],
                ],
            ],
        ] );

        // Get issues for a specific post
        register_rest_route( $this->namespace, '/audit/post/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_issues' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ] );

        // Apply recommendation to a post
        register_rest_route( $this->namespace, '/audit/apply/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'apply_recommendation' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ],
                    'title' => [
                        'type'    => 'string',
                        'default' => '',
                    ],
                    'description' => [
                        'type'    => 'string',
                        'default' => '',
                    ],
                ],
            ],
        ] );

        // Get audit summary/stats
        register_rest_route( $this->namespace, '/audit/summary', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_summary' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get cached audit results or run a new audit if none exist.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_audit( $request ) {
        // Check for cached results (valid for 1 hour)
        $cached = get_transient( 'SAMAN_SEO_audit_results' );

        if ( $cached ) {
            $cached['from_cache'] = true;
            return $this->success( $cached );
        }

        // Run a new audit
        $results = $this->collect_issues( 'any', 100 );
        $results['from_cache'] = false;

        // Cache for 1 hour
        set_transient( 'SAMAN_SEO_audit_results', $results, HOUR_IN_SECONDS );

        return $this->success( $results );
    }

    /**
     * Run a new audit.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function run_audit( $request ) {
        $post_type = $request->get_param( 'post_type' ) ?: 'any';
        $limit     = min( absint( $request->get_param( 'limit' ) ), 500 ); // Max 500 posts

        // Clear old cache
        delete_transient( 'SAMAN_SEO_audit_results' );

        $results = $this->collect_issues( $post_type, $limit );
        $results['from_cache'] = false;
        $results['ran_at'] = current_time( 'mysql' );

        // Cache results
        set_transient( 'SAMAN_SEO_audit_results', $results, HOUR_IN_SECONDS );

        return $this->success( $results, __( 'Audit completed successfully.', 'saman-seo' ) );
    }

    /**
     * Get issues for a specific post.
     *
     * Uses the enhanced calculate_seo_score() function that returns
     * 14 metrics across 4 categories.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_post_issues( $request ) {
        $post_id = $request->get_param( 'id' );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return $this->error( __( 'Post not found.', 'saman-seo' ), 'not_found', 404 );
        }

        // Use the enhanced scoring function.
        $score_data = calculate_seo_score( $post );

        // Get focus keyphrase for context.
        $meta            = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );
        $focus_keyphrase = isset( $meta['focus_keyphrase'] ) ? $meta['focus_keyphrase'] : '';

        // Extract failing metrics as issues.
        $issues = $this->extract_issues_from_metrics( $score_data['metrics'] ?? [] );

        // Build recommendation if needed.
        $recommendation = $this->build_recommendation( $post );

        return $this->success( [
            'post_id'             => $post_id,
            'title'               => get_the_title( $post ),
            'edit_url'            => get_edit_post_link( $post_id, 'raw' ),
            'permalink'           => get_permalink( $post_id ),
            'score'               => $score_data['score'],
            'level'               => $score_data['level'],
            'label'               => $score_data['label'],
            'summary'             => $score_data['summary'],
            'focus_keyphrase'     => $focus_keyphrase,
            'has_keyphrase'       => $score_data['has_keyphrase'] ?? ! empty( $focus_keyphrase ),
            'metrics'             => $score_data['metrics'],
            'metrics_by_category' => $this->group_metrics_by_category( $score_data['metrics'] ?? [] ),
            'issues'              => $issues,
            'recommendation'      => $recommendation,
        ] );
    }

    /**
     * Group metrics by category for UI display.
     *
     * @param array $metrics Metrics array.
     * @return array Grouped metrics.
     */
    private function group_metrics_by_category( $metrics ) {
        $groups = [
            'basic'     => [ 'label' => __( 'Basic SEO', 'saman-seo' ), 'items' => [] ],
            'keyword'   => [ 'label' => __( 'Keyword Optimization', 'saman-seo' ), 'items' => [] ],
            'structure' => [ 'label' => __( 'Content Structure', 'saman-seo' ), 'items' => [] ],
            'links'     => [ 'label' => __( 'Links & Media', 'saman-seo' ), 'items' => [] ],
        ];

        foreach ( $metrics as $metric ) {
            $category = $metric['category'] ?? 'basic';
            if ( isset( $groups[ $category ] ) ) {
                $groups[ $category ]['items'][] = $metric;
            }
        }

        return $groups;
    }

    /**
     * Extract failing metrics as issues list.
     *
     * @param array $metrics Metrics array.
     * @return array Issues array.
     */
    private function extract_issues_from_metrics( $metrics ) {
        $issues = [];

        foreach ( $metrics as $metric ) {
            if ( empty( $metric['is_pass'] ) ) {
                $issues[] = [
                    'key'         => $metric['key'],
                    'severity'    => $metric['score'] === 0 ? 'high' : 'medium',
                    'issue_label' => $metric['issue_label'] ?? $metric['label'],
                    'message'     => $metric['status'],
                    'type'        => $metric['key'],
                    'category'    => $metric['category'] ?? 'basic',
                ];
            }
        }

        return $issues;
    }

    /**
     * Build recommendation for a post.
     *
     * @param \WP_Post $post Post object.
     * @return array|null Recommendation data.
     */
    private function build_recommendation( $post ) {
        $meta = (array) get_post_meta( $post->ID, Post_Meta::META_KEY, true );

        if ( ! empty( $meta['title'] ) && ! empty( $meta['description'] ) ) {
            return null;
        }

        $post_type_descriptions = get_option( 'SAMAN_SEO_post_type_meta_descriptions', [] );

        $title_suggestion = '';
        if ( function_exists( 'Saman\SEO\Helpers\generate_title_from_template' ) ) {
            $title_suggestion = generate_title_from_template( $post );
        }
        if ( empty( $title_suggestion ) ) {
            $title_suggestion = get_the_title( $post );
        }

        $excerpt = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
        if ( empty( $excerpt ) && ! empty( $post_type_descriptions[ $post->post_type ] ) ) {
            $excerpt = $post_type_descriptions[ $post->post_type ];
        }

        return [
            'suggested_title'       => $title_suggestion,
            'suggested_description' => $excerpt,
        ];
    }

    /**
     * Apply a recommendation to a post.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function apply_recommendation( $request ) {
        $post_id     = $request->get_param( 'id' );
        $title       = $request->get_param( 'title' );
        $description = $request->get_param( 'description' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $this->error( __( 'Post not found.', 'saman-seo' ), 'not_found', 404 );
        }

        // Get current meta
        $meta = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );

        // Update meta fields
        $updated = false;
        if ( ! empty( $title ) ) {
            $meta['title'] = sanitize_text_field( $title );
            $updated = true;
        }
        if ( ! empty( $description ) ) {
            $meta['description'] = sanitize_textarea_field( $description );
            $updated = true;
        }

        if ( $updated ) {
            update_post_meta( $post_id, Post_Meta::META_KEY, $meta );

            // Clear audit cache
            delete_transient( 'SAMAN_SEO_audit_results' );
        }

        return $this->success( [
            'post_id' => $post_id,
            'meta'    => $meta,
        ], __( 'Recommendation applied successfully.', 'saman-seo' ) );
    }

    /**
     * Get audit summary/stats.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_summary( $request ) {
        $cached = get_transient( 'SAMAN_SEO_audit_results' );

        if ( $cached ) {
            return $this->success( [
                'stats'      => $cached['stats'],
                'scanned'    => $cached['scanned'],
                'from_cache' => true,
                'ran_at'     => $cached['ran_at'] ?? null,
            ] );
        }

        return $this->success( [
            'stats'      => null,
            'scanned'    => 0,
            'from_cache' => false,
            'ran_at'     => null,
        ] );
    }

    /**
     * Collect SEO issues from posts.
     *
     * @param string $post_type Post type to audit.
     * @param int    $limit     Maximum posts to scan.
     * @return array
     */
    private function collect_issues( $post_type = 'any', $limit = 100 ) {
        $data = [
            'issues'          => [],
            'stats'           => [
                'severity' => [
                    'high'   => 0,
                    'medium' => 0,
                    'low'    => 0,
                ],
                'types'            => [],
                'total'            => 0,
                'posts_with_issues' => 0,
            ],
            'scanned'         => 0,
            'recommendations' => [],
            'posts_scanned'   => [],
        ];

        $query = new \WP_Query( [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'no_found_rows'  => true,
        ] );

        $post_type_descriptions = get_option( 'SAMAN_SEO_post_type_meta_descriptions', [] );
        if ( ! is_array( $post_type_descriptions ) ) {
            $post_type_descriptions = [];
        }

        $posts_with_issues = [];

        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $title   = get_the_title();
            $post    = get_post( $post_id );
            $content = get_the_content( null, false, $post );
            $meta    = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );

            ++$data['scanned'];

            $data['posts_scanned'][] = [
                'id'        => $post_id,
                'title'     => $title,
                'edit_url'  => get_edit_post_link( $post_id, 'raw' ),
                'post_type' => $post->post_type,
            ];

            // Check meta title
            if ( empty( $meta['title'] ) ) {
                $data = $this->add_issue( $data, [
                    'post_id'  => $post_id,
                    'title'    => $title,
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'severity' => 'high',
                    'message'  => __( 'Missing meta title.', 'saman-seo' ),
                    'action'   => __( 'Add a descriptive SEO title.', 'saman-seo' ),
                    'type'     => 'title_missing',
                ] );
                $posts_with_issues[ $post_id ] = true;
                $this->ensure_recommendation( $data['recommendations'], $post, $post_type_descriptions );
            } elseif ( strlen( $meta['title'] ) > 65 ) {
                $data = $this->add_issue( $data, [
                    'post_id'  => $post_id,
                    'title'    => $title,
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'severity' => 'medium',
                    'message'  => sprintf( __( 'Meta title too long (%d characters).', 'saman-seo' ), strlen( $meta['title'] ) ),
                    'action'   => __( 'Shorten to under 65 characters.', 'saman-seo' ),
                    'type'     => 'title_length',
                ] );
                $posts_with_issues[ $post_id ] = true;
            }

            // Check meta description
            if ( empty( $meta['description'] ) ) {
                $data = $this->add_issue( $data, [
                    'post_id'  => $post_id,
                    'title'    => $title,
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'severity' => 'high',
                    'message'  => __( 'Missing meta description.', 'saman-seo' ),
                    'action'   => __( 'Add a keyword-rich summary.', 'saman-seo' ),
                    'type'     => 'description_missing',
                ] );
                $posts_with_issues[ $post_id ] = true;
                $this->ensure_recommendation( $data['recommendations'], $post, $post_type_descriptions );
            } elseif ( strlen( $meta['description'] ) > 160 ) {
                $data = $this->add_issue( $data, [
                    'post_id'  => $post_id,
                    'title'    => $title,
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'severity' => 'low',
                    'message'  => sprintf( __( 'Meta description too long (%d characters).', 'saman-seo' ), strlen( $meta['description'] ) ),
                    'action'   => __( 'Shorten to under 160 characters.', 'saman-seo' ),
                    'type'     => 'description_length',
                ] );
                $posts_with_issues[ $post_id ] = true;
            }

            // Check for images without alt text
            $img_count = substr_count( $content, '<img' );
            $alt_count = preg_match_all( '/<img[^>]+alt=["\'][^"\']+["\']/', $content );
            if ( $img_count > 0 && $alt_count < $img_count ) {
                $missing = $img_count - $alt_count;
                $data = $this->add_issue( $data, [
                    'post_id'  => $post_id,
                    'title'    => $title,
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'severity' => 'medium',
                    'message'  => sprintf( _n( '%d image missing alt text.', '%d images missing alt text.', $missing, 'saman-seo' ), $missing ),
                    'action'   => __( 'Add descriptive alt attributes.', 'saman-seo' ),
                    'type'     => 'missing_alt',
                ] );
                $posts_with_issues[ $post_id ] = true;
            }

            // Check content length (low priority)
            $word_count = str_word_count( wp_strip_all_tags( $content ) );
            if ( $word_count < 300 && $word_count > 0 ) {
                $data = $this->add_issue( $data, [
                    'post_id'  => $post_id,
                    'title'    => $title,
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'severity' => 'low',
                    'message'  => sprintf( __( 'Low word count (%d words).', 'saman-seo' ), $word_count ),
                    'action'   => __( 'Consider adding more content (300+ words recommended).', 'saman-seo' ),
                    'type'     => 'low_word_count',
                ] );
                $posts_with_issues[ $post_id ] = true;
            }

            // Check for H1 tags
            if ( ! preg_match( '/<h1[^>]*>/', $content ) && $post->post_type !== 'page' ) {
                $data = $this->add_issue( $data, [
                    'post_id'  => $post_id,
                    'title'    => $title,
                    'edit_url' => get_edit_post_link( $post_id, 'raw' ),
                    'severity' => 'low',
                    'message'  => __( 'No H1 heading in content.', 'saman-seo' ),
                    'action'   => __( 'Consider adding a main heading.', 'saman-seo' ),
                    'type'     => 'missing_h1',
                ] );
                $posts_with_issues[ $post_id ] = true;
            }
        }

        wp_reset_postdata();

        $data['stats']['posts_with_issues'] = count( $posts_with_issues );

        // Sort types by count
        arsort( $data['stats']['types'] );

        // Convert recommendations to array
        $data['recommendations'] = array_values( $data['recommendations'] );

        return $data;
    }

    /**
     * Analyze a single post for SEO issues.
     *
     * @param \WP_Post $post Post object.
     * @return array
     */
    private function analyze_single_post( $post ) {
        $issues = [];
        $meta   = (array) get_post_meta( $post->ID, Post_Meta::META_KEY, true );
        $content = $post->post_content;

        // Meta title checks
        if ( empty( $meta['title'] ) ) {
            $issues[] = [
                'severity' => 'high',
                'type'     => 'title_missing',
                'message'  => __( 'Missing meta title.', 'saman-seo' ),
            ];
        } elseif ( strlen( $meta['title'] ) > 65 ) {
            $issues[] = [
                'severity' => 'medium',
                'type'     => 'title_length',
                'message'  => sprintf( __( 'Meta title too long (%d characters).', 'saman-seo' ), strlen( $meta['title'] ) ),
            ];
        }

        // Meta description checks
        if ( empty( $meta['description'] ) ) {
            $issues[] = [
                'severity' => 'high',
                'type'     => 'description_missing',
                'message'  => __( 'Missing meta description.', 'saman-seo' ),
            ];
        } elseif ( strlen( $meta['description'] ) > 160 ) {
            $issues[] = [
                'severity' => 'low',
                'type'     => 'description_length',
                'message'  => sprintf( __( 'Meta description too long (%d characters).', 'saman-seo' ), strlen( $meta['description'] ) ),
            ];
        }

        // Image alt check
        $img_count = substr_count( $content, '<img' );
        $alt_count = preg_match_all( '/<img[^>]+alt=["\'][^"\']+["\']/', $content );
        if ( $img_count > 0 && $alt_count < $img_count ) {
            $issues[] = [
                'severity' => 'medium',
                'type'     => 'missing_alt',
                'message'  => sprintf( __( '%d images missing alt text.', 'saman-seo' ), $img_count - $alt_count ),
            ];
        }

        // Word count
        $word_count = str_word_count( wp_strip_all_tags( $content ) );
        if ( $word_count < 300 && $word_count > 0 ) {
            $issues[] = [
                'severity' => 'low',
                'type'     => 'low_word_count',
                'message'  => sprintf( __( 'Low word count (%d words).', 'saman-seo' ), $word_count ),
            ];
        }

        // Calculate score
        $score = 100;
        foreach ( $issues as $issue ) {
            switch ( $issue['severity'] ) {
                case 'high':
                    $score -= 25;
                    break;
                case 'medium':
                    $score -= 10;
                    break;
                case 'low':
                    $score -= 5;
                    break;
            }
        }
        $score = max( 0, $score );

        // Build recommendation
        $recommendation = null;
        if ( empty( $meta['title'] ) || empty( $meta['description'] ) ) {
            $post_type_descriptions = get_option( 'SAMAN_SEO_post_type_meta_descriptions', [] );

            $title_suggestion = '';
            if ( function_exists( 'Saman\SEO\Helpers\generate_title_from_template' ) ) {
                $title_suggestion = generate_title_from_template( $post );
            }
            if ( empty( $title_suggestion ) ) {
                $title_suggestion = get_the_title( $post );
            }

            $excerpt = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
            if ( empty( $excerpt ) && ! empty( $post_type_descriptions[ $post->post_type ] ) ) {
                $excerpt = $post_type_descriptions[ $post->post_type ];
            }

            $recommendation = [
                'suggested_title'       => $title_suggestion,
                'suggested_description' => $excerpt,
            ];
        }

        return [
            'issues'         => $issues,
            'score'          => $score,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Add an issue to the data array.
     *
     * @param array $data  Data array.
     * @param array $issue Issue data.
     * @return array
     */
    private function add_issue( $data, $issue ) {
        $data['issues'][] = $issue;
        ++$data['stats']['total'];

        $severity = $issue['severity'];
        if ( isset( $data['stats']['severity'][ $severity ] ) ) {
            ++$data['stats']['severity'][ $severity ];
        }

        $type = $issue['type'] ?? 'general';
        $data['stats']['types'][ $type ] = ( $data['stats']['types'][ $type ] ?? 0 ) + 1;

        return $data;
    }

    /**
     * Build a recommendation for a post.
     *
     * @param array    $recommendations Current recommendations.
     * @param \WP_Post $post            Post object.
     * @param array    $type_descriptions Default descriptions by post type.
     */
    private function ensure_recommendation( &$recommendations, $post, $type_descriptions ) {
        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        if ( isset( $recommendations[ $post->ID ] ) ) {
            return;
        }

        $title_suggestion = '';
        if ( function_exists( 'Saman\SEO\Helpers\generate_title_from_template' ) ) {
            $title_suggestion = generate_title_from_template( $post );
        }
        if ( empty( $title_suggestion ) ) {
            $title_suggestion = get_the_title( $post );
        }

        $excerpt = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
        if ( empty( $excerpt ) && ! empty( $type_descriptions[ $post->post_type ] ) ) {
            $excerpt = $type_descriptions[ $post->post_type ];
        }

        $tags = get_the_tags( $post->ID );
        if ( $tags ) {
            $tag_names = wp_list_pluck( $tags, 'name' );
        } else {
            $tag_names = wp_list_pluck( get_the_category( $post->ID ), 'name' );
        }

        $recommendations[ $post->ID ] = [
            'post_id'               => $post->ID,
            'title'                 => get_the_title( $post ),
            'edit_url'              => get_edit_post_link( $post->ID, 'raw' ),
            'suggested_title'       => $title_suggestion,
            'suggested_description' => $excerpt,
            'suggested_tags'        => array_filter( (array) $tag_names ),
        ];
    }
}
