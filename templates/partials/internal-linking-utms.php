<?php
/**
 * Internal Linking — UTM Templates UI.
 *
 * @package WPSEOPilot
 */

$form_action = admin_url( 'admin-post.php' );
$editing     = $template_to_edit ?? null;

?>
<div class="wpseopilot-links__split">
	<div class="wpseopilot-card">
		<h3><?php esc_html_e( 'UTM Templates', 'wp-seo-pilot' ); ?></h3>
		<p><?php esc_html_e( 'Define reusable parameter sets so rules and categories can inherit consistent tracking.', 'wp-seo-pilot' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'utm_source', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'utm_medium', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'utm_campaign', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Apply to', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Append mode', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-seo-pilot' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $utm_templates ) ) : ?>
					<tr>
						<td colspan="7"><?php esc_html_e( 'No UTM templates yet.', 'wp-seo-pilot' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $utm_templates as $template ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $template['name'] ); ?></strong></td>
							<td><?php echo esc_html( $template['utm_source'] ); ?></td>
							<td><?php echo esc_html( $template['utm_medium'] ); ?></td>
							<td><?php echo esc_html( $template['utm_campaign'] ); ?></td>
							<td><?php echo esc_html( ucfirst( $template['apply_to'] ) ); ?></td>
							<td>
								<?php
								switch ( $template['append_mode'] ) {
									case 'always_overwrite':
										esc_html_e( 'Always overwrite', 'wp-seo-pilot' );
										break;
									case 'never':
										esc_html_e( 'Never overwrite', 'wp-seo-pilot' );
										break;
									default:
										esc_html_e( 'Append if missing', 'wp-seo-pilot' );
										break;
								}
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'utms', 'template' => $template['id'] ], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'wp-seo-pilot' ); ?></a>
								<?php $delete_url = wp_nonce_url( add_query_arg( [ 'action' => 'wpseopilot_delete_link_template', 'template' => $template['id'] ], admin_url( 'admin-post.php' ) ), 'wpseopilot_delete_link_template' ); ?>
								| <a class="submitdelete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'wp-seo-pilot' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="wpseopilot-card">
		<h3><?php echo esc_html( $editing ? __( 'Edit template', 'wp-seo-pilot' ) : __( 'Add template', 'wp-seo-pilot' ) ); ?></h3>
		<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="wpseopilot-links__utm-form">
			<?php wp_nonce_field( 'wpseopilot_save_link_template' ); ?>
			<input type="hidden" name="action" value="wpseopilot_save_link_template" />
			<?php if ( $editing ) : ?>
				<input type="hidden" name="template[id]" value="<?php echo esc_attr( $editing['id'] ); ?>" />
				<input type="hidden" name="template[created_at]" value="<?php echo esc_attr( $editing['created_at'] ?? time() ); ?>" />
			<?php endif; ?>

			<label>
				<span><?php esc_html_e( 'Name', 'wp-seo-pilot' ); ?></span>
				<input type="text" name="template[name]" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" required />
			</label>
			<div class="wpseopilot-grid">
				<label>
					<span><?php esc_html_e( 'utm_source', 'wp-seo-pilot' ); ?></span>
					<input type="text" name="template[utm_source]" value="<?php echo esc_attr( $editing['utm_source'] ?? '' ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'utm_medium', 'wp-seo-pilot' ); ?></span>
					<input type="text" name="template[utm_medium]" value="<?php echo esc_attr( $editing['utm_medium'] ?? '' ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'utm_campaign', 'wp-seo-pilot' ); ?></span>
					<input type="text" name="template[utm_campaign]" value="<?php echo esc_attr( $editing['utm_campaign'] ?? '' ); ?>" />
				</label>
			</div>
			<div class="wpseopilot-grid">
				<label>
					<span><?php esc_html_e( 'utm_term (optional)', 'wp-seo-pilot' ); ?></span>
					<input type="text" name="template[utm_term]" value="<?php echo esc_attr( $editing['utm_term'] ?? '' ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'utm_content (optional)', 'wp-seo-pilot' ); ?></span>
					<input type="text" name="template[utm_content]" value="<?php echo esc_attr( $editing['utm_content'] ?? '' ); ?>" />
				</label>
			</div>
			<fieldset>
				<legend><?php esc_html_e( 'Apply to', 'wp-seo-pilot' ); ?></legend>
				<label class="wpseopilot-links__choice">
					<input type="radio" name="template[apply_to]" value="internal" <?php checked( 'internal', $editing['apply_to'] ?? 'both' ); ?> />
					<span><?php esc_html_e( 'Internal links only', 'wp-seo-pilot' ); ?></span>
				</label>
				<label class="wpseopilot-links__choice">
					<input type="radio" name="template[apply_to]" value="external" <?php checked( 'external', $editing['apply_to'] ?? 'both' ); ?> />
					<span><?php esc_html_e( 'External links only', 'wp-seo-pilot' ); ?></span>
				</label>
				<label class="wpseopilot-links__choice">
					<input type="radio" name="template[apply_to]" value="both" <?php checked( 'both', $editing['apply_to'] ?? 'both' ); ?> />
					<span><?php esc_html_e( 'Both', 'wp-seo-pilot' ); ?></span>
				</label>
			</fieldset>
			<fieldset>
				<legend><?php esc_html_e( 'Append mode', 'wp-seo-pilot' ); ?></legend>
				<label class="wpseopilot-links__choice">
					<input type="radio" name="template[append_mode]" value="append_if_missing" <?php checked( 'append_if_missing', $editing['append_mode'] ?? 'append_if_missing' ); ?> />
					<span><?php esc_html_e( 'Append if missing', 'wp-seo-pilot' ); ?></span>
				</label>
				<label class="wpseopilot-links__choice">
					<input type="radio" name="template[append_mode]" value="always_overwrite" <?php checked( 'always_overwrite', $editing['append_mode'] ?? '' ); ?> />
					<span><?php esc_html_e( 'Always overwrite', 'wp-seo-pilot' ); ?></span>
				</label>
				<label class="wpseopilot-links__choice">
					<input type="radio" name="template[append_mode]" value="never" <?php checked( 'never', $editing['append_mode'] ?? '' ); ?> />
					<span><?php esc_html_e( 'Never overwrite existing params', 'wp-seo-pilot' ); ?></span>
				</label>
			</fieldset>

			<div class="wpseopilot-links__helper">
				<strong><?php esc_html_e( 'Token helper', 'wp-seo-pilot' ); ?></strong>
				<p><?php esc_html_e( 'Tokens: {post_id}, {post_slug}, {post_type}, {post_title}, {primary_category}, {keyword}, {rule_id}, {date:Ymd}, {author}, {site_name}.', 'wp-seo-pilot' ); ?></p>
			</div>

			<?php submit_button( $editing ? __( 'Update template', 'wp-seo-pilot' ) : __( 'Save template', 'wp-seo-pilot' ) ); ?>
		</form>
	</div>
</div>
