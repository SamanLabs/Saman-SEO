<?php
/**
 * Internal Linking — Categories management.
 *
 * @package WPSEOPilot
 */

$form_action      = admin_url( 'admin-post.php' );
$editing          = $category_to_edit ?? null;
$category_id      = $editing['id'] ?? '';
$template_lookup  = [];
foreach ( $utm_templates as $template ) {
	$template_lookup[ $template['id'] ] = $template['name'];
}

?>
<div class="wpseopilot-links__split">
	<div class="wpseopilot-card">
		<h3><?php esc_html_e( 'Categories', 'saman-labs-seo' ); ?></h3>
		<p><?php esc_html_e( 'Group rules, pick a color, optionally inherit UTMs and set per-category caps.', 'saman-labs-seo' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Color', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Default UTM Template', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Category cap', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Rule count', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'saman-labs-seo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $categories ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'No categories yet.', 'saman-labs-seo' ); ?></td>
					</tr>
				<?php else : ?>
						<?php foreach ( $categories as $category ) :
							$cap            = $category['category_cap'] ?? 0;
							$count          = $category_usage[ $category['id'] ] ?? 0;
							$template_label = $template_lookup[ $category['default_utm'] ] ?? '';
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $category['name'] ); ?></strong>
							<div class="description"><?php echo esc_html( $category['description'] ); ?></div>
						</td>
						<td><span class="wpseopilot-color-chip" style="background-color: <?php echo esc_attr( $category['color'] ); ?>;"></span></td>
						<td><?php echo esc_html( $template_label ?: '—' ); ?></td>
						<td><?php echo esc_html( $cap ?: '—' ); ?></td>
						<td><?php echo esc_html( $count ); ?></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'categories', 'category' => $category['id'] ], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'saman-labs-seo' ); ?></a>
							<div>
								<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpseopilot-links__delete-category">
									<input type="hidden" name="action" value="samanlabs_seo_delete_link_category" />
									<input type="hidden" name="category" value="<?php echo esc_attr( $category['id'] ); ?>" />
									<?php wp_nonce_field( 'samanlabs_seo_delete_link_category' ); ?>
									<?php if ( $count > 0 ) : ?>
										<label>
											<span class="screen-reader-text"><?php esc_html_e( 'Reassign rules to', 'saman-labs-seo' ); ?></span>
											<select name="reassign">
												<option value="__none__"><?php esc_html_e( 'Remove category from rules', 'saman-labs-seo' ); ?></option>
												<?php foreach ( $categories as $option ) :
													if ( $option['id'] === $category['id'] ) {
														continue;
													}
													?>
													<option value="<?php echo esc_attr( $option['id'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
												<?php endforeach; ?>
											</select>
										</label>
									<?php endif; ?>
									<button type="submit" class="button button-link-delete" <?php disabled( empty( $categories ) ); ?>><?php esc_html_e( 'Delete', 'saman-labs-seo' ); ?></button>
								</form>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="wpseopilot-card">
		<h3><?php echo esc_html( $editing ? __( 'Edit category', 'saman-labs-seo' ) : __( 'Add category', 'saman-labs-seo' ) ); ?></h3>
		<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="wpseopilot-links__category-form">
			<?php wp_nonce_field( 'samanlabs_seo_save_link_category' ); ?>
			<input type="hidden" name="action" value="samanlabs_seo_save_link_category" />
			<?php if ( $editing ) : ?>
				<input type="hidden" name="category[id]" value="<?php echo esc_attr( $category_id ); ?>" />
				<input type="hidden" name="category[created_at]" value="<?php echo esc_attr( $editing['created_at'] ?? time() ); ?>" />
			<?php endif; ?>
			<label>
				<span><?php esc_html_e( 'Name', 'saman-labs-seo' ); ?></span>
				<input type="text" name="category[name]" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" required />
			</label>
			<label>
				<span><?php esc_html_e( 'Color', 'saman-labs-seo' ); ?></span>
				<input type="color" name="category[color]" value="<?php echo esc_attr( $editing['color'] ?? ( $category_default['color'] ?? '#4f46e5' ) ); ?>" />
			</label>
			<label>
				<span><?php esc_html_e( 'Description', 'saman-labs-seo' ); ?></span>
				<textarea name="category[description]" rows="3"><?php echo esc_textarea( $editing['description'] ?? '' ); ?></textarea>
			</label>
			<label>
				<span><?php esc_html_e( 'Default UTM Template', 'saman-labs-seo' ); ?></span>
				<select name="category[default_utm]">
					<option value=""><?php esc_html_e( 'None', 'saman-labs-seo' ); ?></option>
					<?php foreach ( $utm_templates as $template ) : ?>
						<option value="<?php echo esc_attr( $template['id'] ); ?>" <?php selected( $editing['default_utm'] ?? '', $template['id'] ); ?>>
							<?php echo esc_html( $template['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( 'Category-level cap (per page)', 'saman-labs-seo' ); ?></span>
				<input type="number" min="0" max="50" name="category[category_cap]" value="<?php echo esc_attr( $editing['category_cap'] ?? '' ); ?>" />
				<p class="description"><?php esc_html_e( '0 or blank = no extra cap.', 'saman-labs-seo' ); ?></p>
			</label>

			<?php submit_button( $editing ? __( 'Update category', 'saman-labs-seo' ) : __( 'Save category', 'saman-labs-seo' ) ); ?>
		</form>
	</div>
</div>
