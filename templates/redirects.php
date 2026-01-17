<?php
/**
 * Redirect manager template.
 *
 * @var array $redirects
 *
 * @package Saman\SEO
 */

?>
<?php
$SAMAN_SEO_prefill = isset( $_GET['prefill'] ) ? sanitize_text_field( wp_unslash( $_GET['prefill'] ) ) : '';

// Render top bar
\Saman\SEO\Admin_Topbar::render( 'redirects' );
?>
<div class="wrap saman-seo-page">

	<?php
	$suggestions = get_option( 'SAMAN_SEO_monitor_slugs', [] );
	if ( ! empty( $suggestions ) ) :
		?>
		<div class="saman-seo-card" style="margin-bottom: 20px; border-left: 4px solid #ffba00;">
			<h2><?php esc_html_e( 'Ã¢Å¡Â Ã¯Â¸Â Detected Slug Changes', 'saman-seo' ); ?></h2>
			<p><?php esc_html_e( 'The following posts have changed their URL structure. You should probably create redirects to prevent 404 errors.', 'saman-seo' ); ?></p>
			<table class="wp-list-table widefat striped" style="margin-top: 10px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Old Path', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'New Target', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'saman-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $suggestions as $key => $suggestion ) : ?>
						<tr>
							<td><code><?php echo esc_html( $suggestion['source'] ); ?></code></td>
							<td><a href="<?php echo esc_url( $suggestion['target'] ); ?>" target="_blank"><?php echo esc_html( $suggestion['target'] ); ?></a></td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( [ 'prefill' => $suggestion['source'], 'target_prefill' => $suggestion['target'] ], admin_url( 'admin.php?page=saman-seo-redirects' ) ) ); ?>" class="button button-small button-primary" onclick="
									event.preventDefault();
									document.getElementById('source').value = '<?php echo esc_js( $suggestion['source'] ); ?>';
									document.getElementById('target').value = '<?php echo esc_js( $suggestion['target'] ); ?>';
									document.getElementById('source').focus();
								"><?php esc_html_e( 'Use', 'saman-seo' ); ?></a>
								
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'SAMAN_SEO_dismiss_slug', 'key' => $key ], admin_url( 'admin-post.php' ) ), 'SAMAN_SEO_dismiss_slug' ) ); ?>" class="button button-small button-link-delete" style="color: #a00;"><?php esc_html_e( 'Dismiss', 'saman-seo' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="saman-seo-card">
		<?php wp_nonce_field( 'SAMAN_SEO_redirect' ); ?>
		<input type="hidden" name="action" value="SAMAN_SEO_save_redirect" />
		<table class="form-table">
			<tr>
				<th scope="row"><label for="source"><?php esc_html_e( 'Source path', 'saman-seo' ); ?></label></th>
				<td><input type="text" name="source" id="source" class="regular-text" placeholder="/old-url" value="<?php echo esc_attr( $SAMAN_SEO_prefill ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="target"><?php esc_html_e( 'Target URL', 'saman-seo' ); ?></label></th>
				<td><input type="url" name="target" id="target" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="status_code"><?php esc_html_e( 'Status', 'saman-seo' ); ?></label></th>
				<td>
					<select name="status_code" id="status_code">
						<option value="301">301 <?php esc_html_e( 'Permanent', 'saman-seo' ); ?></option>
						<option value="302">302 <?php esc_html_e( 'Temporary', 'saman-seo' ); ?></option>
						<option value="307">307</option>
						<option value="410">410 <?php esc_html_e( 'Gone', 'saman-seo' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Add redirect', 'saman-seo' ) ); ?>
	</form>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Source', 'saman-seo' ); ?></th>
				<th><?php esc_html_e( 'Target', 'saman-seo' ); ?></th>
				<th><?php esc_html_e( 'Status', 'saman-seo' ); ?></th>
				<th><?php esc_html_e( 'Hits', 'saman-seo' ); ?></th>
				<th><?php esc_html_e( 'Last hit', 'saman-seo' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'saman-seo' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $redirects ) : ?>
				<?php foreach ( $redirects as $SAMAN_SEO_redirect ) : ?>
					<tr>
						<td><?php echo esc_html( $SAMAN_SEO_redirect->source ); ?></td>
						<td><a href="<?php echo esc_url( $SAMAN_SEO_redirect->target ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $SAMAN_SEO_redirect->target ); ?></a></td>
						<td><?php echo esc_html( $SAMAN_SEO_redirect->status_code ); ?></td>
						<td><?php echo esc_html( $SAMAN_SEO_redirect->hits ); ?></td>
						<td><?php echo esc_html( $SAMAN_SEO_redirect->last_hit ?: 'Ã¢â‚¬â€' ); ?></td>
						<td>
							<a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'SAMAN_SEO_delete_redirect', 'id' => $SAMAN_SEO_redirect->id ], admin_url( 'admin-post.php' ) ), 'SAMAN_SEO_redirect_delete' ) ); ?>"><?php esc_html_e( 'Delete', 'saman-seo' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No redirects configured.', 'saman-seo' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
