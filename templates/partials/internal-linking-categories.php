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
		<h3><?php esc_html_e( 'Categories', 'wp-seo-pilot' ); ?></h3>
		<p><?php esc_html_e( 'Group rules, pick a color, optionally inherit UTMs and set per-category caps.', 'wp-seo-pilot' ); ?></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Color', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Default UTM Template', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Category cap', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Rule count', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wp-seo-pilot' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $categories ) ) : ?>
					<tr>
						<td colspan="6"><?php esc_html_e( 'No categories yet.', 'wp-seo-pilot' ); ?></td>
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
							<a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'categories', 'category' => $category['id'] ], $page_url ) ); ?>"><?php esc_html_e( 'Edit', 'wp-seo-pilot' ); ?></a>
							<div>
								<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpseopilot-links__delete-category">
									<input type="hidden" name="action" value="wpseopilot_delete_link_category" />
									<input type="hidden" name="category" value="<?php echo esc_attr( $category['id'] ); ?>" />
									<?php wp_nonce_field( 'wpseopilot_delete_link_category' ); ?>
									<?php if ( $count > 0 ) : ?>
										<label>
											<span class="screen-reader-text"><?php esc_html_e( 'Reassign rules to', 'wp-seo-pilot' ); ?></span>
											<select name="reassign">
												<option value="__none__"><?php esc_html_e( 'Remove category from rules', 'wp-seo-pilot' ); ?></option>
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
									<button type="submit" class="button button-link-delete" <?php disabled( empty( $categories ) ); ?>><?php esc_html_e( 'Delete', 'wp-seo-pilot' ); ?></button>
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
		<h3><?php echo esc_html( $editing ? __( 'Edit category', 'wp-seo-pilot' ) : __( 'Add category', 'wp-seo-pilot' ) ); ?></h3>
		<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="wpseopilot-links__category-form">
			<?php wp_nonce_field( 'wpseopilot_save_link_category' ); ?>
			<input type="hidden" name="action" value="wpseopilot_save_link_category" />
			<?php if ( $editing ) : ?>
				<input type="hidden" name="category[id]" value="<?php echo esc_attr( $category_id ); ?>" />
				<input type="hidden" name="category[created_at]" value="<?php echo esc_attr( $editing['created_at'] ?? time() ); ?>" />
			<?php endif; ?>
			<label>
				<span><?php esc_html_e( 'Name', 'wp-seo-pilot' ); ?></span>
				<input type="text" name="category[name]" value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" required />
			</label>
			<label>
				<span><?php esc_html_e( 'Color', 'wp-seo-pilot' ); ?></span>
				<input type="color" name="category[color]" value="<?php echo esc_attr( $editing['color'] ?? ( $category_default['color'] ?? '#4f46e5' ) ); ?>" />
			</label>
			<label>
				<span><?php esc_html_e( 'Description', 'wp-seo-pilot' ); ?></span>
				<textarea name="category[description]" rows="3"><?php echo esc_textarea( $editing['description'] ?? '' ); ?></textarea>
			</label>
			<label>
				<span><?php esc_html_e( 'Default UTM Template', 'wp-seo-pilot' ); ?></span>
				<select name="category[default_utm]">
					<option value=""><?php esc_html_e( 'None', 'wp-seo-pilot' ); ?></option>
					<?php foreach ( $utm_templates as $template ) : ?>
						<option value="<?php echo esc_attr( $template['id'] ); ?>" <?php selected( $editing['default_utm'] ?? '', $template['id'] ); ?>>
							<?php echo esc_html( $template['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( 'Category-level cap (per page)', 'wp-seo-pilot' ); ?></span>
				<input type="number" min="0" max="50" name="category[category_cap]" value="<?php echo esc_attr( $editing['category_cap'] ?? '' ); ?>" />
				<p class="description"><?php esc_html_e( '0 or blank = no extra cap.', 'wp-seo-pilot' ); ?></p>
			</label>

			<?php submit_button( $editing ? __( 'Update category', 'wp-seo-pilot' ) : __( 'Save category', 'wp-seo-pilot' ) ); ?>
		</form>
	</div>
</div>
