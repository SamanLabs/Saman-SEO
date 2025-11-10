<?php
/**
 * Internal Linking — Rules table.
 *
 * @package WPSEOPilot
 */

$filter_status   = $filters['status'] ?? '';
$filter_category = $filters['category'] ?? '';
$filter_types    = (array) ( $filters['post_type'] ?? [] );
if ( empty( $filter_types ) ) {
	$filter_types = [ '__all__' ];
}
$search_term     = $filters['search'] ?? '';

$category_map  = [];
foreach ( $categories as $category ) {
	$category_map[ $category['id'] ] = $category;
}

$template_map = [];
foreach ( $utm_templates as $template ) {
	$template_map[ $template['id'] ] = $template;
}

$bulk_actions = [
	''              => __( 'Bulk actions', 'wp-seo-pilot' ),
	'activate'      => __( 'Activate', 'wp-seo-pilot' ),
	'deactivate'    => __( 'Deactivate', 'wp-seo-pilot' ),
	'delete'        => __( 'Delete', 'wp-seo-pilot' ),
	'change_category' => __( 'Change category', 'wp-seo-pilot' ),
];

$rules_empty = empty( $rules );

?>
<div class="wpseopilot-card wpseopilot-links__rules">
	<div class="wpseopilot-links__panel-head">
		<div>
			<h2><?php esc_html_e( 'Rules', 'wp-seo-pilot' ); ?></h2>
			<p><?php esc_html_e( 'Define how keywords become links, inherit settings from categories, and control limits + placements.', 'wp-seo-pilot' ); ?></p>
		</div>
		<div>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'new' ], $page_url ) ); ?>">
				<?php esc_html_e( 'Add rule', 'wp-seo-pilot' ); ?>
			</a>
		</div>
	</div>

	<form class="wpseopilot-links__filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>" />
		<input type="hidden" name="tab" value="rules" />

		<label>
			<span><?php esc_html_e( 'Status', 'wp-seo-pilot' ); ?></span>
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'wp-seo-pilot' ); ?></option>
				<option value="active" <?php selected( 'active', $filter_status ); ?>><?php esc_html_e( 'Active', 'wp-seo-pilot' ); ?></option>
				<option value="inactive" <?php selected( 'inactive', $filter_status ); ?>><?php esc_html_e( 'Inactive', 'wp-seo-pilot' ); ?></option>
			</select>
		</label>

		<label>
			<span><?php esc_html_e( 'Category', 'wp-seo-pilot' ); ?></span>
			<select name="category">
				<option value=""><?php esc_html_e( 'All categories', 'wp-seo-pilot' ); ?></option>
				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category['id'] ); ?>" <?php selected( $category['id'], $filter_category ); ?>>
						<?php echo esc_html( $category['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<label>
			<span><?php esc_html_e( 'Post type', 'wp-seo-pilot' ); ?></span>
			<select name="post_type[]" multiple size="3">
				<option value="__all__" <?php selected( in_array( '__all__', $filter_types, true ), true ); ?>><?php esc_html_e( 'All post types', 'wp-seo-pilot' ); ?></option>
				<?php foreach ( $post_types as $type => $label ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>" <?php selected( in_array( $type, $filter_types, true ), true ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="wpseopilot-links__search">
			<span><?php esc_html_e( 'Search', 'wp-seo-pilot' ); ?></span>
			<input type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Title or keyword', 'wp-seo-pilot' ); ?>" />
		</label>

		<div class="wpseopilot-links__filter-actions">
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wp-seo-pilot' ); ?></button>
			<a class="button button-link" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'rules' ], $page_url ) ); ?>"><?php esc_html_e( 'Reset', 'wp-seo-pilot' ); ?></a>
		</div>
	</form>

	<?php if ( $rules_empty ) : ?>
		<div class="wpseopilot-links__empty">
			<p><?php esc_html_e( 'No rules yet. Create your first internal link rule.', 'wp-seo-pilot' ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'new' ], $page_url ) ); ?>"><?php esc_html_e( 'Create rule', 'wp-seo-pilot' ); ?></a>
		</div>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpseopilot-links__table-form" data-bulk-form>
			<?php wp_nonce_field( 'wpseopilot_bulk_link_rules' ); ?>
			<input type="hidden" name="action" value="wpseopilot_bulk_link_rules" />

			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input type="checkbox" data-select-all />
						</td>
						<th><?php esc_html_e( 'Title', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Category', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Keywords', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Destination', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'UTM Template', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Limits', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wp-seo-pilot' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rules as $rule ) :
						$category_id = $rule['category'] ?? '';
						$category    = $category_map[ $category_id ] ?? null;
						$template_id = $rule['utm_template'] ?? 'inherit';
						$template    = $template_map[ $template_id ] ?? null;
						$keywords    = implode( ', ', $rule['keywords'] );
						$destination = $rule['destination'] ?? [];
						$limit_block = $rule['limits']['max_block'] ?? null;
						$max_page_raw = $rule['limits']['max_page'] ?? '';
						$max_page_default = absint( $settings['default_max_links_per_page'] ?? 1 );
						$max_page    = ( '' === $max_page_raw ) ? $max_page_default : absint( $max_page_raw );
						$status      = $rule['status'] ?? 'inactive';
						$destination_label = '';

						if ( 'post' === ( $destination['type'] ?? 'post' ) && ! empty( $destination['post'] ) ) {
							$post_obj = get_post( $destination['post'] );
							if ( $post_obj ) {
								$destination_label = sprintf(
									'%1$s (%2$s)',
									get_the_title( $post_obj ),
									$post_obj->post_type
								);
							} else {
								$destination_label = __( 'Post not found', 'wp-seo-pilot' );
							}
						} elseif ( ! empty( $destination['url'] ) ) {
							$destination_label = $destination['url'];
						}

						$duplicate_url = wp_nonce_url(
							add_query_arg(
								[
									'action' => 'wpseopilot_duplicate_link_rule',
									'rule'   => $rule['id'],
								],
								admin_url( 'admin-post.php' )
							),
							'wpseopilot_duplicate_link_rule'
						);

						$toggle_url = wp_nonce_url(
							add_query_arg(
								[
									'action' => 'wpseopilot_toggle_link_rule',
									'rule'   => $rule['id'],
									'status' => ( 'active' === $status ) ? 'inactive' : 'active',
								],
								admin_url( 'admin-post.php' )
							),
							'wpseopilot_toggle_link_rule'
						);

						$delete_url = wp_nonce_url(
							add_query_arg(
								[
									'action' => 'wpseopilot_delete_link_rule',
									'rule'   => $rule['id'],
								],
								admin_url( 'admin-post.php' )
							),
							'wpseopilot_delete_link_rule'
						);
					?>
					<tr>
						<th scope="row" class="check-column">
							<input type="checkbox" name="rule_ids[]" value="<?php echo esc_attr( $rule['id'] ); ?>" />
						</th>
						<td>
							<strong><a href="<?php echo esc_url( $tab_url( 'edit', [ 'rule' => $rule['id'] ] ) ); ?>"><?php echo esc_html( $rule['title'] ); ?></a></strong>
							<div class="row-actions">
								<span class="edit"><a href="<?php echo esc_url( $tab_url( 'edit', [ 'rule' => $rule['id'] ] ) ); ?>"><?php esc_html_e( 'Edit', 'wp-seo-pilot' ); ?></a> | </span>
								<span class="duplicate"><a href="<?php echo esc_url( $duplicate_url ); ?>"><?php esc_html_e( 'Duplicate', 'wp-seo-pilot' ); ?></a> | </span>
								<span class="toggle"><a href="<?php echo esc_url( $toggle_url ); ?>"><?php echo ( 'active' === $status ) ? esc_html__( 'Deactivate', 'wp-seo-pilot' ) : esc_html__( 'Activate', 'wp-seo-pilot' ); ?></a> | </span>
								<span class="delete"><a class="submitdelete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'wp-seo-pilot' ); ?></a></span>
							</div>
						</td>
						<td>
							<?php if ( $category ) : ?>
								<span class="wpseopilot-pill" style="--wpseopilot-pill-color: <?php echo esc_attr( $category['color'] ); ?>">
									<?php echo esc_html( $category['name'] ); ?>
								</span>
							<?php else : ?>
								<?php esc_html_e( '—', 'wp-seo-pilot' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $keywords ); ?></td>
						<td>
							<?php if ( 'post' === ( $destination['type'] ?? 'post' ) && ! empty( $destination['post'] ) ) : ?>
								<?php echo esc_html( $destination_label ); ?>
							<?php elseif ( ! empty( $destination_label ) ) : ?>
								<a href="<?php echo esc_url( $destination['url'] ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( $destination_label ); ?>
								</a>
							<?php else : ?>
								<?php esc_html_e( '—', 'wp-seo-pilot' ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php
							if ( 'inherit' === $template_id ) {
								echo esc_html__( 'Inherit', 'wp-seo-pilot' );
							} elseif ( $template ) {
								echo esc_html( $template['name'] );
							} else {
								echo esc_html__( 'Custom', 'wp-seo-pilot' );
							}
							?>
						</td>
						<td>
							<?php echo esc_html( sprintf( '%1$d · %2$s', $max_page, ( null === $limit_block ) ? '—' : $limit_block ) ); ?>
						</td>
						<td>
							<span class="wpseopilot-status wpseopilot-status--<?php echo ( 'active' === $status ) ? 'success' : 'muted'; ?>">
								<?php echo ( 'active' === $status ) ? esc_html__( 'Active', 'wp-seo-pilot' ) : esc_html__( 'Inactive', 'wp-seo-pilot' ); ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="wpseopilot-links__bulk">
				<label>
					<span class="screen-reader-text"><?php esc_html_e( 'Bulk actions', 'wp-seo-pilot' ); ?></span>
					<select name="bulk_action" data-bulk-action>
						<?php foreach ( $bulk_actions as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="wpseopilot-links__bulk-category" data-bulk-category hidden>
					<span class="screen-reader-text"><?php esc_html_e( 'Select category', 'wp-seo-pilot' ); ?></span>
					<select name="bulk_category">
						<option value="__none__"><?php esc_html_e( 'Remove category', 'wp-seo-pilot' ); ?></option>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category['id'] ); ?>"><?php echo esc_html( $category['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Apply', 'wp-seo-pilot' ); ?></button>
			</div>
		</form>
	<?php endif; ?>
</div>
