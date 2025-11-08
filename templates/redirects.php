<?php
/**
 * Redirect manager template.
 *
 * @var array $redirects
 *
 * @package WPSEOPilot
 */

?>
<?php $prefill = isset( $_GET['prefill'] ) ? sanitize_text_field( wp_unslash( $_GET['prefill'] ) ) : ''; ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Redirect Manager', 'wp-seo-pilot' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpseopilot-card">
		<?php wp_nonce_field( 'wpseopilot_redirect' ); ?>
		<input type="hidden" name="action" value="wpseopilot_save_redirect" />
		<table class="form-table">
			<tr>
				<th scope="row"><label for="source"><?php esc_html_e( 'Source path', 'wp-seo-pilot' ); ?></label></th>
				<td><input type="text" name="source" id="source" class="regular-text" placeholder="/old-url" value="<?php echo esc_attr( $prefill ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="target"><?php esc_html_e( 'Target URL', 'wp-seo-pilot' ); ?></label></th>
				<td><input type="url" name="target" id="target" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="status_code"><?php esc_html_e( 'Status', 'wp-seo-pilot' ); ?></label></th>
				<td>
					<select name="status_code" id="status_code">
						<option value="301">301 <?php esc_html_e( 'Permanent', 'wp-seo-pilot' ); ?></option>
						<option value="302">302 <?php esc_html_e( 'Temporary', 'wp-seo-pilot' ); ?></option>
						<option value="307">307</option>
						<option value="410">410 <?php esc_html_e( 'Gone', 'wp-seo-pilot' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Add redirect', 'wp-seo-pilot' ) ); ?>
	</form>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Source', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Target', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Hits', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Last hit', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-seo-pilot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $redirects ) : ?>
				<?php foreach ( $redirects as $redirect ) : ?>
					<tr>
						<td><?php echo esc_html( $redirect->source ); ?></td>
						<td><a href="<?php echo esc_url( $redirect->target ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $redirect->target ); ?></a></td>
						<td><?php echo esc_html( $redirect->status_code ); ?></td>
						<td><?php echo esc_html( $redirect->hits ); ?></td>
						<td><?php echo esc_html( $redirect->last_hit ?: '—' ); ?></td>
						<td>
							<a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'wpseopilot_delete_redirect', 'id' => $redirect->id ], admin_url( 'admin-post.php' ) ), 'wpseopilot_redirect_delete' ) ); ?>"><?php esc_html_e( 'Delete', 'wp-seo-pilot' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No redirects configured.', 'wp-seo-pilot' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
