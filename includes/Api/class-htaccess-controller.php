<?php
/**
 * Htaccess REST API Controller
 *
 * Provides endpoints for editing the .htaccess file.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Htaccess_Controller class.
 */
class Htaccess_Controller extends REST_Controller {

    /**
     * Path to .htaccess file.
     *
     * @var string
     */
    private $htaccess_path;

    /**
     * Path to backups directory.
     *
     * @var string
     */
    private $backup_dir;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->namespace     = 'saman-seo/v1';
        $this->rest_base     = 'htaccess';
        $this->htaccess_path = ABSPATH . '.htaccess';
        $this->backup_dir    = WP_CONTENT_DIR . '/saman-seo-backups/htaccess/';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get .htaccess content
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_content' ],
                'permission_callback' => [ $this, 'check_permission' ],
            ]
        );

        // Save .htaccess content
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_content' ],
                'permission_callback' => [ $this, 'check_permission' ],
                'args'                => [
                    'content' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                ],
            ]
        );

        // Restore from backup
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/restore',
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'restore_backup' ],
                'permission_callback' => [ $this, 'check_permission' ],
                'args'                => [
                    'backup' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_file_name',
                    ],
                ],
            ]
        );
    }

    /**
     * Get .htaccess content.
     *
     * @return \WP_REST_Response
     */
    public function get_content() {
        if ( ! file_exists( $this->htaccess_path ) ) {
            return $this->success( [
                'content' => '',
                'backups' => [],
                'exists'  => false,
            ] );
        }

        if ( ! is_readable( $this->htaccess_path ) ) {
            return $this->error( 'Unable to read .htaccess file. Check file permissions.' );
        }

        $content = file_get_contents( $this->htaccess_path );

        return $this->success( [
            'content' => $content,
            'backups' => $this->get_backups(),
            'exists'  => true,
        ] );
    }

    /**
     * Save .htaccess content.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_content( $request ) {
        $content = $request->get_param( 'content' );

        // Create backup first
        $backup_result = $this->create_backup();
        if ( is_wp_error( $backup_result ) ) {
            return $this->error( 'Failed to create backup: ' . $backup_result->get_error_message() );
        }

        // Validate content (basic syntax check)
        $validation = $this->validate_content( $content );
        if ( is_wp_error( $validation ) ) {
            return $this->error( $validation->get_error_message() );
        }

        // Write to file
        $result = file_put_contents( $this->htaccess_path, $content );
        if ( $result === false ) {
            return $this->error( 'Failed to write to .htaccess file. Check file permissions.' );
        }

        return $this->success( [
            'message' => 'File saved successfully',
            'backups' => $this->get_backups(),
        ] );
    }

    /**
     * Restore from backup.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function restore_backup( $request ) {
        $backup_file = $request->get_param( 'backup' );
        $backup_path = $this->backup_dir . $backup_file;

        if ( ! file_exists( $backup_path ) ) {
            return $this->error( 'Backup file not found' );
        }

        // Create backup of current state before restore
        $this->create_backup();

        // Read backup content
        $content = file_get_contents( $backup_path );
        if ( $content === false ) {
            return $this->error( 'Failed to read backup file' );
        }

        // Restore
        $result = file_put_contents( $this->htaccess_path, $content );
        if ( $result === false ) {
            return $this->error( 'Failed to restore backup' );
        }

        return $this->success( [
            'content' => $content,
            'message' => 'Backup restored successfully',
        ] );
    }

    /**
     * Create a backup of the current .htaccess file.
     *
     * @return true|\WP_Error
     */
    private function create_backup() {
        if ( ! file_exists( $this->htaccess_path ) ) {
            return true; // Nothing to backup
        }

        // Ensure backup directory exists
        if ( ! file_exists( $this->backup_dir ) ) {
            wp_mkdir_p( $this->backup_dir );
        }

        // Create backup filename with timestamp
        $timestamp   = current_time( 'Y-m-d-His' );
        $backup_file = $this->backup_dir . "htaccess-{$timestamp}.bak";

        $result = copy( $this->htaccess_path, $backup_file );
        if ( ! $result ) {
            return new \WP_Error( 'backup_failed', 'Failed to create backup file' );
        }

        // Clean up old backups (keep last 10)
        $this->cleanup_old_backups();

        return true;
    }

    /**
     * Get list of backups.
     *
     * @return array
     */
    private function get_backups() {
        if ( ! file_exists( $this->backup_dir ) ) {
            return [];
        }

        $files   = glob( $this->backup_dir . 'htaccess-*.bak' );
        $backups = [];

        if ( ! empty( $files ) ) {
            // Sort by modification time (newest first)
            usort( $files, function ( $a, $b ) {
                return filemtime( $b ) - filemtime( $a );
            } );

            foreach ( $files as $file ) {
                $backups[] = [
                    'file' => basename( $file ),
                    'date' => wp_date( 'M j, Y g:i a', filemtime( $file ) ),
                    'size' => size_format( filesize( $file ) ),
                ];
            }
        }

        return $backups;
    }

    /**
     * Clean up old backups.
     */
    private function cleanup_old_backups() {
        $files = glob( $this->backup_dir . 'htaccess-*.bak' );
        if ( empty( $files ) || count( $files ) <= 10 ) {
            return;
        }

        // Sort by modification time (oldest first)
        usort( $files, function ( $a, $b ) {
            return filemtime( $a ) - filemtime( $b );
        } );

        // Delete oldest files
        $to_delete = array_slice( $files, 0, count( $files ) - 10 );
        foreach ( $to_delete as $file ) {
            @unlink( $file );
        }
    }

    /**
     * Validate .htaccess content.
     *
     * @param string $content Content to validate.
     * @return true|\WP_Error
     */
    private function validate_content( $content ) {
        // Check for obvious syntax errors

        // Unclosed IfModule
        $if_count    = preg_match_all( '/<IfModule\s/i', $content );
        $endif_count = preg_match_all( '/<\/IfModule>/i', $content );
        if ( $if_count !== $endif_count ) {
            return new \WP_Error( 'syntax_error', 'Unclosed <IfModule> directive detected' );
        }

        // Unclosed Directory
        $dir_count    = preg_match_all( '/<Directory\s/i', $content );
        $enddir_count = preg_match_all( '/<\/Directory>/i', $content );
        if ( $dir_count !== $enddir_count ) {
            return new \WP_Error( 'syntax_error', 'Unclosed <Directory> directive detected' );
        }

        // Unclosed Files
        $files_count    = preg_match_all( '/<Files\s/i', $content );
        $endfiles_count = preg_match_all( '/<\/Files>/i', $content );
        if ( $files_count !== $endfiles_count ) {
            return new \WP_Error( 'syntax_error', 'Unclosed <Files> directive detected' );
        }

        return true;
    }
}
