<?php
/**
 * Redirect manager template.
 *
 * @var array $redirects
 *
 * @package WPSEOPilot
 */

?>
<?php
$wpseopilot_prefill = isset( $_GET['prefill'] ) ? sanitize_text_field( wp_unslash( $_GET['prefill'] ) ) : '';

// Render top bar
\WPSEOPilot\Admin_Topbar::render( 'redirects' );
?>
<div class="wrap wpseopilot-page">

	<?php
	$suggestions = get_option( 'wpseopilot_monitor_slugs', [] );
	if ( ! empty( $suggestions ) ) :
		?>
		<div class="wpseopilot-card" style="margin-bottom: 20px; border-left: 4px solid #ffba00;">
			<h2><?php esc_html_e( '⚠️ Detected Slug Changes', 'saman-labs-seo' ); ?></h2>
			<p><?php esc_html_e( 'The following posts have changed their URL structure. You should probably create redirects to prevent 404 errors.', 'saman-labs-seo' ); ?></p>
			<table class="wp-list-table widefat striped" style="margin-top: 10px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Old Path', 'saman-labs-seo' ); ?></th>
						<th><?php esc_html_e( 'New Target', 'saman-labs-seo' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'saman-labs-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $suggestions as $key => $suggestion ) : ?>
						<tr>
							<td><code><?php echo esc_html( $suggestion['source'] ); ?></code></td>
							<td><a href="<?php echo esc_url( $suggestion['target'] ); ?>" target="_blank"><?php echo esc_html( $suggestion['target'] ); ?></a></td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( [ 'prefill' => $suggestion['source'], 'target_prefill' => $suggestion['target'] ], admin_url( 'admin.php?page=wpseopilot-redirects' ) ) ); ?>" class="button button-small button-primary" onclick="
									event.preventDefault();
									document.getElementById('source').value = '<?php echo esc_js( $suggestion['source'] ); ?>';
									document.getElementById('target').value = '<?php echo esc_js( $suggestion['target'] ); ?>';
									document.getElementById('source').focus();
								"><?php esc_html_e( 'Use', 'saman-labs-seo' ); ?></a>
								
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wpseopilot_dismiss_slug', 'key' => $key ], admin_url( 'admin-post.php' ) ), 'wpseopilot_dismiss_slug' ) ); ?>" class="button button-small button-link-delete" style="color: #a00;"><?php esc_html_e( 'Dismiss', 'saman-labs-seo' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpseopilot-card">
		<?php wp_nonce_field( 'wpseopilot_redirect' ); ?>
		<input type="hidden" name="action" value="wpseopilot_save_redirect" />
		<table class="form-table">
			<tr>
				<th scope="row"><label for="source"><?php esc_html_e( 'Source path', 'saman-labs-seo' ); ?></label></th>
				<td><input type="text" name="source" id="source" class="regular-text" placeholder="/old-url" value="<?php echo esc_attr( $wpseopilot_prefill ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="target"><?php esc_html_e( 'Target URL', 'saman-labs-seo' ); ?></label></th>
				<td><input type="url" name="target" id="target" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="status_code"><?php esc_html_e( 'Status', 'saman-labs-seo' ); ?></label></th>
				<td>
					<select name="status_code" id="status_code">
						<option value="301">301 <?php esc_html_e( 'Permanent', 'saman-labs-seo' ); ?></option>
						<option value="302">302 <?php esc_html_e( 'Temporary', 'saman-labs-seo' ); ?></option>
						<option value="307">307</option>
						<option value="410">410 <?php esc_html_e( 'Gone', 'saman-labs-seo' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Add redirect', 'saman-labs-seo' ) ); ?>
	</form>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Source', 'saman-labs-seo' ); ?></th>
				<th><?php esc_html_e( 'Target', 'saman-labs-seo' ); ?></th>
				<th><?php esc_html_e( 'Status', 'saman-labs-seo' ); ?></th>
				<th><?php esc_html_e( 'Hits', 'saman-labs-seo' ); ?></th>
				<th><?php esc_html_e( 'Last hit', 'saman-labs-seo' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'saman-labs-seo' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $redirects ) : ?>
				<?php foreach ( $redirects as $wpseopilot_redirect ) : ?>
					<tr>
						<td><?php echo esc_html( $wpseopilot_redirect->source ); ?></td>
						<td><a href="<?php echo esc_url( $wpseopilot_redirect->target ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $wpseopilot_redirect->target ); ?></a></td>
						<td><?php echo esc_html( $wpseopilot_redirect->status_code ); ?></td>
						<td><?php echo esc_html( $wpseopilot_redirect->hits ); ?></td>
						<td><?php echo esc_html( $wpseopilot_redirect->last_hit ?: '—' ); ?></td>
						<td>
							<a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wpseopilot_delete_redirect', 'id' => $wpseopilot_redirect->id ], admin_url( 'admin-post.php' ) ), 'wpseopilot_redirect_delete' ) ); ?>"><?php esc_html_e( 'Delete', 'saman-labs-seo' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No redirects configured.', 'saman-labs-seo' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
