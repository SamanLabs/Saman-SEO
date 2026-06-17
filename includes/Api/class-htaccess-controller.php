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
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_content' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		// Save .htaccess content
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_content' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'content' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);

		// Restore from backup
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/restore',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'restore_backup' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'backup' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_file_name',
					),
				),
			)
		);
	}

	/**
	 * Initialise WordPress filesystem access.
	 *
	 * @return \WP_Filesystem_Base|\WP_Error
	 */
	private function init_filesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;

		$status = WP_Filesystem();
		if ( true !== $status || ! $wp_filesystem ) {
			return new \WP_Error( 'filesystem_error', __( 'Unable to initialise filesystem access.', 'saman-seo' ) );
		}

		return $wp_filesystem;
	}

	/**
	 * Get .htaccess content.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_content() {
		$wp_filesystem = $this->init_filesystem();
		if ( is_wp_error( $wp_filesystem ) ) {
			return $this->error( $wp_filesystem->get_error_message() );
		}

		if ( ! $wp_filesystem->exists( $this->htaccess_path ) ) {
			return $this->success(
				array(
					'content' => '',
					'backups' => array(),
					'exists'  => false,
				)
			);
		}

		$content = $wp_filesystem->get_contents( $this->htaccess_path );
		if ( false === $content ) {
			return $this->error( 'Unable to read .htaccess file. Check file permissions.' );
		}

		return $this->success(
			array(
				'content' => $content,
				'backups' => $this->get_backups(),
				'exists'  => true,
			)
		);
	}

	/**
	 * Save .htaccess content.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_content( $request ) {
		$wp_filesystem = $this->init_filesystem();
		if ( is_wp_error( $wp_filesystem ) ) {
			return $this->error( $wp_filesystem->get_error_message() );
		}

		$content = $request->get_param( 'content' );

		// Create backup first.
		$backup_result = $this->create_backup( $wp_filesystem );
		if ( is_wp_error( $backup_result ) ) {
			return $this->error( 'Failed to create backup: ' . $backup_result->get_error_message() );
		}

		// Validate content.
		$validation = $this->validate_content( $content );
		if ( is_wp_error( $validation ) ) {
			return $this->error( $validation->get_error_message() );
		}

		// Write to file.
		$result = $wp_filesystem->put_contents( $this->htaccess_path, $content, FS_CHMOD_FILE );
		if ( false === $result ) {
			return $this->error( 'Failed to write to .htaccess file. Check file permissions.' );
		}

		return $this->success(
			array(
				'message' => 'File saved successfully',
				'backups' => $this->get_backups(),
			)
		);
	}

	/**
	 * Restore from backup.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function restore_backup( $request ) {
		$wp_filesystem = $this->init_filesystem();
		if ( is_wp_error( $wp_filesystem ) ) {
			return $this->error( $wp_filesystem->get_error_message() );
		}

		$backup_file = sanitize_file_name( $request->get_param( 'backup' ) );
		$backup_path = $this->backup_dir . $backup_file;

		// Ensure the resolved path is still inside the backup directory.
		$real_backup = realpath( $backup_path );
		$real_dir    = realpath( $this->backup_dir );
		if ( false === $real_backup || false === $real_dir || 0 !== strpos( $real_backup, $real_dir . DIRECTORY_SEPARATOR ) ) {
			return $this->error( 'Backup file not found' );
		}

		if ( ! $wp_filesystem->exists( $backup_path ) ) {
			return $this->error( 'Backup file not found' );
		}

		// Create backup of current state before restore.
		$this->create_backup( $wp_filesystem );

		// Read backup content.
		$content = $wp_filesystem->get_contents( $backup_path );
		if ( false === $content ) {
			return $this->error( 'Failed to read backup file' );
		}

		// Validate backup content before restoring.
		$validation = $this->validate_content( $content );
		if ( is_wp_error( $validation ) ) {
			return $this->error( 'Backup content failed validation: ' . $validation->get_error_message() );
		}

		// Restore.
		$result = $wp_filesystem->put_contents( $this->htaccess_path, $content, FS_CHMOD_FILE );
		if ( false === $result ) {
			return $this->error( 'Failed to restore backup' );
		}

		return $this->success(
			array(
				'content' => $content,
				'message' => 'Backup restored successfully',
			)
		);
	}

	/**
	 * Create a backup of the current .htaccess file.
	 *
	 * @param \WP_Filesystem_Base $wp_filesystem Filesystem object.
	 * @return true|\WP_Error
	 */
	private function create_backup( $wp_filesystem ) {
		if ( ! $wp_filesystem->exists( $this->htaccess_path ) ) {
			return true; // Nothing to backup.
		}

		// Ensure backup directory exists.
		if ( ! $wp_filesystem->exists( $this->backup_dir ) ) {
			wp_mkdir_p( $this->backup_dir );
		}

		// Create backup filename with timestamp.
		$timestamp   = current_time( 'Y-m-d-His' );
		$backup_file = $this->backup_dir . "htaccess-{$timestamp}.bak";

		$content = $wp_filesystem->get_contents( $this->htaccess_path );
		if ( false === $content ) {
			return new \WP_Error( 'backup_failed', 'Failed to read .htaccess file for backup' );
		}

		$result = $wp_filesystem->put_contents( $backup_file, $content, FS_CHMOD_FILE );
		if ( false === $result ) {
			return new \WP_Error( 'backup_failed', 'Failed to create backup file' );
		}

		// Clean up old backups (keep last 10).
		$this->cleanup_old_backups( $wp_filesystem );

		return true;
	}

	/**
	 * Get list of backups.
	 *
	 * @return array
	 */
	private function get_backups() {
		if ( ! file_exists( $this->backup_dir ) ) {
			return array();
		}

		$files   = glob( $this->backup_dir . 'htaccess-*.bak' );
		$backups = array();

		if ( ! empty( $files ) ) {
			// Sort by modification time (newest first).
			usort(
				$files,
				function ( $a, $b ) {
					return filemtime( $b ) - filemtime( $a );
				}
			);

			foreach ( $files as $file ) {
				$backups[] = array(
					'file' => basename( $file ),
					'date' => wp_date( 'M j, Y g:i a', filemtime( $file ) ),
					'size' => size_format( filesize( $file ) ),
				);
			}
		}

		return $backups;
	}

	/**
	 * Clean up old backups.
	 *
	 * @param \WP_Filesystem_Base $wp_filesystem Filesystem object.
	 */
	private function cleanup_old_backups( $wp_filesystem ) {
		$files = glob( $this->backup_dir . 'htaccess-*.bak' );
		if ( empty( $files ) || count( $files ) <= 10 ) {
			return;
		}

		// Sort by modification time (oldest first).
		usort(
			$files,
			function ( $a, $b ) {
				return filemtime( $a ) - filemtime( $b );
			}
		);

		// Delete oldest files.
		$to_delete = array_slice( $files, 0, count( $files ) - 10 );
		foreach ( $to_delete as $file ) {
			$wp_filesystem->delete( $file );
		}
	}

	/**
	 * Validate .htaccess content.
	 *
	 * Rejects dangerous directives and unbalanced block tags.
	 *
	 * @param string $content Content to validate.
	 * @return true|\WP_Error
	 */
	private function validate_content( $content ) {
		// Reject directives that can change PHP execution or handlers.
		$dangerous = array(
			'php_value',
			'php_flag',
			'php_admin_value',
			'php_admin_flag',
			'addhandler',
			'sethandler',
			'action',
		);

		$lines = preg_split( '/\r\n|\r|\n/', $content );
		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			// Skip comments and blank lines.
			if ( '' === $trimmed || '#' === $trimmed[0] ) {
				continue;
			}

			$lower = strtolower( $trimmed );
			foreach ( $dangerous as $directive ) {
				if ( 0 === strpos( $lower, $directive ) ) {
					return new \WP_Error(
						'unsafe_directive',
						sprintf(
							/* translators: %s: Apache directive name */
							__( 'Unsafe directive detected: %s', 'saman-seo' ),
							esc_html( $directive )
						)
					);
				}
			}
		}

		// Check for balanced block tags.
		$pairs = array(
			array( '/<IfModule\s/i', '/<\/IfModule>/i', 'IfModule' ),
			array( '/<If\b/i', '/<\/If>/i', 'If' ),
			array( '/<Directory\b/i', '/<\/Directory>/i', 'Directory' ),
			array( '/<Files\b/i', '/<\/Files>/i', 'Files' ),
		);

		foreach ( $pairs as [ $open, $close, $name ] ) {
			$open_count  = preg_match_all( $open, $content );
			$close_count = preg_match_all( $close, $content );
			if ( $open_count !== $close_count ) {
				return new \WP_Error(
					'syntax_error',
					sprintf(
						/* translators: %s: Block tag name */
						__( 'Unclosed <%s> directive detected', 'saman-seo' ),
						esc_html( $name )
					)
				);
			}
		}

		return true;
	}
}
