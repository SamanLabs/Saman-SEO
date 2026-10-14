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
	 * Read access: site administrators (super admins on multisite).
	 *
	 * The .htaccess file is shared by every site in a network, so a
	 * per-site administrator must not be able to touch it.
	 *
	 * @return bool
	 */
	public function permission_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( is_multisite() && ! is_super_admin() ) {
			return false;
		}

		return true;
	}

	/**
	 * Write access: read access plus file modifications being allowed.
	 *
	 * Honors DISALLOW_FILE_MODS (via wp_is_file_mod_allowed()) and
	 * DISALLOW_FILE_EDIT, which site owners set precisely to stop admins
	 * from changing executable server configuration from the dashboard.
	 *
	 * @return bool|\WP_Error
	 */
	public function write_permission_check() {
		if ( ! $this->permission_check() ) {
			return false;
		}

		if ( ! self::file_mods_allowed() ) {
			return new \WP_Error(
				'saman_seo_file_mods_disabled',
				__( 'File editing is disabled on this site (DISALLOW_FILE_MODS or DISALLOW_FILE_EDIT), so .htaccess cannot be changed from the dashboard.', 'saman-seo' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Whether the site allows file modifications from the dashboard.
	 *
	 * @return bool
	 */
	public static function file_mods_allowed() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
			return false;
		}

		if ( function_exists( 'wp_is_file_mod_allowed' ) ) {
			return (bool) wp_is_file_mod_allowed( 'saman_seo_htaccess' );
		}

		return ! ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS );
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
				'permission_callback' => array( $this, 'write_permission_check' ),
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
				'permission_callback' => array( $this, 'write_permission_check' ),
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
				'content'  => $content,
				'backups'  => $this->get_backups(),
				'exists'   => true,
				'writable' => self::file_mods_allowed(),
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

		// Write to file, rolling back if the site stops responding.
		$result = $this->write_and_verify( $wp_filesystem, $content );
		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code() );
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

		// Restore, rolling back if the site stops responding.
		$result = $this->write_and_verify( $wp_filesystem, $content );
		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code() );
		}

		return $this->success(
			array(
				'content' => $content,
				'message' => 'Backup restored successfully',
			)
		);
	}

	/**
	 * Write .htaccess and confirm the site still responds.
	 *
	 * The site is probed before and after the write. If the front page
	 * answered before but returns a 5xx or fails after, the previous
	 * content is put back. Hosts that block loopback requests entirely
	 * (probe fails before the write) skip verification instead of being
	 * locked out of the editor.
	 *
	 * @param \WP_Filesystem_Base $wp_filesystem Filesystem object.
	 * @param string              $content       New content.
	 * @return true|\WP_Error
	 */
	private function write_and_verify( $wp_filesystem, $content ) {
		$existed  = $wp_filesystem->exists( $this->htaccess_path );
		$previous = $existed ? $wp_filesystem->get_contents( $this->htaccess_path ) : '';

		if ( $existed && false === $previous ) {
			return new \WP_Error( 'read_failed', __( 'Unable to read the current .htaccess file. Check file permissions.', 'saman-seo' ) );
		}

		$baseline_ok = self::site_responds( $this->loopback() );

		$result = $wp_filesystem->put_contents( $this->htaccess_path, $content, FS_CHMOD_FILE );
		if ( false === $result ) {
			return new \WP_Error( 'write_failed', __( 'Failed to write to .htaccess file. Check file permissions.', 'saman-seo' ) );
		}

		if ( ! $baseline_ok || self::site_responds( $this->loopback() ) ) {
			return true;
		}

		if ( $existed ) {
			$restored = $wp_filesystem->put_contents( $this->htaccess_path, $previous, FS_CHMOD_FILE );
		} else {
			$restored = $wp_filesystem->delete( $this->htaccess_path );
		}

		if ( false === $restored ) {
			return new \WP_Error(
				'rollback_failed',
				__( 'The new .htaccess rules made the site stop responding and the previous file could not be restored automatically. Restore the latest backup from wp-content/saman-seo-backups/htaccess/ via FTP or your hosting file manager.', 'saman-seo' )
			);
		}

		return new \WP_Error(
			'rolled_back',
			__( 'The new .htaccess rules made the site return a server error, so the previous version was restored automatically. Check the rules for typos or modules your server does not have.', 'saman-seo' )
		);
	}

	/**
	 * Request the front page from the server itself.
	 *
	 * @return array|\WP_Error
	 */
	private function loopback() {
		return wp_remote_get(
			home_url( '/' ),
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'sslverify'   => false,
				'headers'     => array( 'Cache-Control' => 'no-cache' ),
			)
		);
	}

	/**
	 * Whether a loopback response means the site is up.
	 *
	 * @param array|\WP_Error $response HTTP response.
	 * @return bool False on transport errors and 5xx responses.
	 */
	public static function site_responds( $response ) {
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		return $code > 0 && $code < 500;
	}

	/**
	 * Create the backup directory and stop it being served over HTTP.
	 *
	 * @param \WP_Filesystem_Base $wp_filesystem Filesystem object.
	 * @return true|\WP_Error
	 */
	private function ensure_backup_dir( $wp_filesystem ) {
		if ( ! $wp_filesystem->exists( $this->backup_dir ) && ! wp_mkdir_p( $this->backup_dir ) ) {
			return new \WP_Error( 'backup_failed', __( 'Failed to create the backup directory.', 'saman-seo' ) );
		}

		$root = trailingslashit( dirname( $this->backup_dir ) );

		$deny = "# Saman SEO backups: never serve these files.\n"
			. "<IfModule mod_authz_core.c>\n"
			. "\tRequire all denied\n"
			. "</IfModule>\n"
			. "<IfModule !mod_authz_core.c>\n"
			. "\tOrder deny,allow\n"
			. "\tDeny from all\n"
			. "</IfModule>\n";

		$silence = "<?php\n// Silence is golden.\n";

		$files = array(
			$root . '.htaccess'             => $deny,
			$root . 'index.php'             => $silence,
			$this->backup_dir . '.htaccess' => $deny,
			$this->backup_dir . 'index.php' => $silence,
		);

		foreach ( $files as $path => $body ) {
			if ( ! $wp_filesystem->exists( $path ) ) {
				$wp_filesystem->put_contents( $path, $body, FS_CHMOD_FILE );
			}
		}

		return true;
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

		// Ensure the backup directory exists and is not web-readable.
		$dir_result = $this->ensure_backup_dir( $wp_filesystem );
		if ( is_wp_error( $dir_result ) ) {
			return $dir_result;
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
	public function validate_content( $content ) {
		$content = (string) $content;

		// Apache joins lines ending in a backslash; do the same so a
		// directive cannot be split across lines to dodge the checks.
		$joined = preg_replace( '/\\\\[ \t]*(\r\n|\r|\n)/', '', $content );

		$lines = preg_split( '/\r\n|\r|\n/', (string) $joined );
		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			// Skip comments and blank lines.
			if ( '' === $trimmed || '#' === $trimmed[0] ) {
				continue;
			}

			$directive = self::find_unsafe_directive( $trimmed );
			if ( '' !== $directive ) {
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

	/**
	 * Identify a directive that could enable code execution.
	 *
	 * @param string $line Trimmed, non-comment .htaccess line.
	 * @return string Offending directive label, or '' when the line is fine.
	 */
	private static function find_unsafe_directive( $line ) {
		$lower = strtolower( $line );

		// Directives blocked outright: PHP ini overrides (auto_prepend_file,
		// auto_append_file, ...), handler mapping and CGI actions.
		$blocked = array(
			'php_value',
			'php_flag',
			'php_admin_value',
			'php_admin_flag',
			'addhandler',
			'sethandler',
			'forcetype',
			'action',
			'scriptalias',
			'scriptaliasmatch',
		);

		$name = preg_match( '/^([a-z_]+)/', $lower, $m ) ? $m[1] : '';

		if ( in_array( $name, $blocked, true ) ) {
			return $name;
		}

		// Ini settings smuggled in another way.
		if ( preg_match( '/\bauto_(prepend|append)_file\b/', $lower ) ) {
			return 'auto_prepend_file/auto_append_file';
		}

		// AddType mapping files to PHP or CGI.
		if ( 'addtype' === $name && preg_match( '/(php|cgi|phtml|phar)/', $lower ) ) {
			return 'addtype';
		}

		// Options enabling CGI execution or server-side includes with exec.
		if ( 'options' === $name && preg_match( '/(^|[\s+])(execcgi|includes)\b/', substr( $lower, 7 ) ) ) {
			return 'options execcgi';
		}

		// RewriteRule with a handler flag ([H=...] / [handler=...]) or a
		// MIME type flag that maps the target to PHP or CGI ([T=...php]).
		if ( 'rewriterule' === $name ) {
			if ( preg_match( '/\[[^\]]*\b(h|handler)\s*=/', $lower ) ) {
				return 'rewriterule handler';
			}

			if ( preg_match( '/\[[^\]]*\b(t|type)\s*=[^,\]]*(php|cgi)/', $lower ) ) {
				return 'rewriterule type';
			}
		}

		return '';
	}
}
