<?php
/**
 * Internal Linking Ã¢â‚¬â€ UTM Templates UI.
 *
 * @package Saman\SEO
 */

$form_action = admin_url( 'admin-post.php' );
$editing     = $template_to_edit ?? null;

?>
<div class="saman-seo-links__split">
	<div class="saman-seo-card">
		<h3><?php esc_html_e( 'UTM Templates', 'saman-seo' ); ?></h3>
		<p><?php esc_html_e( 'Define reusable parameter sets so rules and categories can inherit consistent tracking.', 'saman-seo' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'utm_source', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'utm_medium', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'utm_campaign', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'Apply to', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'Append mode', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'saman-seo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $utm_templates ) ) : ?>
					<tr>
						<td colspan="7"><?php esc_html_e( 'No UTM templates yet.', 'saman-seo' ); ?></td>
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
										esc_html_e( 'Always overwrite', 'saman-seo' );
										break;
									case 'never':
										esc_html_e( 'Never overwrite', 'saman-seo' );
										break;
									default:
										esc_html_e( 'Append if missing', 'saman-seo' );
										break;
								}
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'utms', 'template' => $template['id'] ], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'saman-seo' ); ?></a>
								<?php $delete_url = wp_nonce_url( add_query_arg( [ 'action' => 'SAMAN_SEO_delete_link_template', 'template' => $template['id'] ], admin_url( 'admin-post.php' ) ), 'SAMAN_SEO_delete_link_template' ); ?>
								| <a class="submitdelete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'saman-seo' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="saman-seo-card">
		<h3><?php echo esc_html( $editing ? __( 'Edit template', 'saman-seo' ) : __( 'Add template', 'saman-seo' ) ); ?></h3>
		<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="saman-seo-links__utm-form">
			<?php wp_nonce_field( 'SAMAN_SEO_save_link_template' ); ?>
			<input type="hidden" name="action" value="SAMAN_SEO_save_link_template" />
			<?php if ( $editing ) : ?>
				<input type="hidden" name="template[id]" value="<?php echo esc_attr( $editing['id'] ); ?>" />
				<input type="hidden" name="template[created_at]" value="<?php echo esc_attr( $editing['created_at'] ?? time() ); ?>" />
			<?php endif; ?>

			<label>
				<span><?php esc_html_e( 'Name', 'saman-seo' ); ?></span>
				<input type="text" name="template[name]" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" required />
			</label>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'utm_source', 'saman-seo' ); ?></span>
					<input type="text" name="template[utm_source]" value="<?php echo esc_attr( $editing['utm_source'] ?? '' ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'utm_medium', 'saman-seo' ); ?></span>
					<input type="text" name="template[utm_medium]" value="<?php echo esc_attr( $editing['utm_medium'] ?? '' ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'utm_campaign', 'saman-seo' ); ?></span>
					<input type="text" name="template[utm_campaign]" value="<?php echo esc_attr( $editing['utm_campaign'] ?? '' ); ?>" />
				</label>
			</div>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'utm_term (optional)', 'saman-seo' ); ?></span>
					<input type="text" name="template[utm_term]" value="<?php echo esc_attr( $editing['utm_term'] ?? '' ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'utm_content (optional)', 'saman-seo' ); ?></span>
					<input type="text" name="template[utm_content]" value="<?php echo esc_attr( $editing['utm_content'] ?? '' ); ?>" />
				</label>
			</div>
			<fieldset>
				<legend><?php esc_html_e( 'Apply to', 'saman-seo' ); ?></legend>
				<label class="saman-seo-links__choice">
					<input type="radio" name="template[apply_to]" value="internal" <?php checked( 'internal', $editing['apply_to'] ?? 'both' ); ?> />
					<span><?php esc_html_e( 'Internal links only', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="radio" name="template[apply_to]" value="external" <?php checked( 'external', $editing['apply_to'] ?? 'both' ); ?> />
					<span><?php esc_html_e( 'External links only', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="radio" name="template[apply_to]" value="both" <?php checked( 'both', $editing['apply_to'] ?? 'both' ); ?> />
					<span><?php esc_html_e( 'Both', 'saman-seo' ); ?></span>
				</label>
			</fieldset>
			<fieldset>
				<legend><?php esc_html_e( 'Append mode', 'saman-seo' ); ?></legend>
				<label class="saman-seo-links__choice">
					<input type="radio" name="template[append_mode]" value="append_if_missing" <?php checked( 'append_if_missing', $editing['append_mode'] ?? 'append_if_missing' ); ?> />
					<span><?php esc_html_e( 'Append if missing', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="radio" name="template[append_mode]" value="always_overwrite" <?php checked( 'always_overwrite', $editing['append_mode'] ?? '' ); ?> />
					<span><?php esc_html_e( 'Always overwrite', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="radio" name="template[append_mode]" value="never" <?php checked( 'never', $editing['append_mode'] ?? '' ); ?> />
					<span><?php esc_html_e( 'Never overwrite existing params', 'saman-seo' ); ?></span>
				</label>
			</fieldset>

			<div class="saman-seo-links__helper">
				<strong><?php esc_html_e( 'Token helper', 'saman-seo' ); ?></strong>
				<p><?php esc_html_e( 'Tokens: {post_id}, {post_slug}, {post_type}, {post_title}, {primary_category}, {keyword}, {rule_id}, {date:Ymd}, {author}, {site_name}.', 'saman-seo' ); ?></p>
			</div>

			<?php submit_button( $editing ? __( 'Update template', 'saman-seo' ) : __( 'Save template', 'saman-seo' ) ); ?>
		</form>
	</div>
</div>
