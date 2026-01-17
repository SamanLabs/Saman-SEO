<?php
/**
 * Internal Linking Ã¢â‚¬â€ Rules table.
 *
 * @package Saman\SEO
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
	''              => __( 'Bulk actions', 'saman-seo' ),
	'activate'      => __( 'Activate', 'saman-seo' ),
	'deactivate'    => __( 'Deactivate', 'saman-seo' ),
	'delete'        => __( 'Delete', 'saman-seo' ),
	'change_category' => __( 'Change category', 'saman-seo' ),
];

$rules_empty = empty( $rules );

?>
<div class="saman-seo-card saman-seo-links__rules">
	<div class="saman-seo-links__panel-head">
		<div>
			<h2><?php esc_html_e( 'Rules', 'saman-seo' ); ?></h2>
			<p><?php esc_html_e( 'Define how keywords become links, inherit settings from categories, and control limits + placements.', 'saman-seo' ); ?></p>
		</div>
		<div>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'new' ], $page_url ) ); ?>">
				<?php esc_html_e( 'Add rule', 'saman-seo' ); ?>
			</a>
		</div>
	</div>

	<form class="saman-seo-links__filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>" />
		<input type="hidden" name="tab" value="rules" />

		<label>
			<span><?php esc_html_e( 'Status', 'saman-seo' ); ?></span>
			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'saman-seo' ); ?></option>
				<option value="active" <?php selected( 'active', $filter_status ); ?>><?php esc_html_e( 'Active', 'saman-seo' ); ?></option>
				<option value="inactive" <?php selected( 'inactive', $filter_status ); ?>><?php esc_html_e( 'Inactive', 'saman-seo' ); ?></option>
			</select>
		</label>

		<label>
			<span><?php esc_html_e( 'Category', 'saman-seo' ); ?></span>
			<select name="category">
				<option value=""><?php esc_html_e( 'All categories', 'saman-seo' ); ?></option>
				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category['id'] ); ?>" <?php selected( $category['id'], $filter_category ); ?>>
						<?php echo esc_html( $category['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<label>
			<span><?php esc_html_e( 'Post type', 'saman-seo' ); ?></span>
			<select name="post_type[]" multiple size="3">
				<option value="__all__" <?php selected( in_array( '__all__', $filter_types, true ), true ); ?>><?php esc_html_e( 'All post types', 'saman-seo' ); ?></option>
				<?php foreach ( $post_types as $type => $label ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>" <?php selected( in_array( $type, $filter_types, true ), true ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="saman-seo-links__search">
			<span><?php esc_html_e( 'Search', 'saman-seo' ); ?></span>
			<input type="search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Title or keyword', 'saman-seo' ); ?>" />
		</label>

		<div class="saman-seo-links__filter-actions">
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'saman-seo' ); ?></button>
			<a class="button button-link" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'rules' ], $page_url ) ); ?>"><?php esc_html_e( 'Reset', 'saman-seo' ); ?></a>
		</div>
	</form>

	<?php if ( $rules_empty ) : ?>
		<div class="saman-seo-links__empty">
			<p><?php esc_html_e( 'No rules yet. Create your first internal link rule.', 'saman-seo' ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( add_query_arg( [ 'tab' => 'new' ], $page_url ) ); ?>"><?php esc_html_e( 'Create rule', 'saman-seo' ); ?></a>
		</div>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="saman-seo-links__table-form" data-bulk-form>
			<?php wp_nonce_field( 'SAMAN_SEO_bulk_link_rules' ); ?>
			<input type="hidden" name="action" value="SAMAN_SEO_bulk_link_rules" />

			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column">
							<input type="checkbox" data-select-all />
						</td>
						<th><?php esc_html_e( 'Title', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Category', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Keywords', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Destination', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'UTM Template', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Limits', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Status', 'saman-seo' ); ?></th>
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
								$destination_label = __( 'Post not found', 'saman-seo' );
							}
						} elseif ( ! empty( $destination['url'] ) ) {
							$destination_label = $destination['url'];
						}

						$duplicate_url = wp_nonce_url(
							add_query_arg(
								[
									'action' => 'SAMAN_SEO_duplicate_link_rule',
									'rule'   => $rule['id'],
								],
								admin_url( 'admin-post.php' )
							),
							'SAMAN_SEO_duplicate_link_rule'
						);

						$toggle_url = wp_nonce_url(
							add_query_arg(
								[
									'action' => 'SAMAN_SEO_toggle_link_rule',
									'rule'   => $rule['id'],
									'status' => ( 'active' === $status ) ? 'inactive' : 'active',
								],
								admin_url( 'admin-post.php' )
							),
							'SAMAN_SEO_toggle_link_rule'
						);

						$delete_url = wp_nonce_url(
							add_query_arg(
								[
									'action' => 'SAMAN_SEO_delete_link_rule',
									'rule'   => $rule['id'],
								],
								admin_url( 'admin-post.php' )
							),
							'SAMAN_SEO_delete_link_rule'
						);
					?>
					<tr>
						<th scope="row" class="check-column">
							<input type="checkbox" name="rule_ids[]" value="<?php echo esc_attr( $rule['id'] ); ?>" />
						</th>
						<td>
							<strong><a href="<?php echo esc_url( $tab_url( 'edit', [ 'rule' => $rule['id'] ] ) ); ?>"><?php echo esc_html( $rule['title'] ); ?></a></strong>
							<div class="row-actions">
								<span class="edit"><a href="<?php echo esc_url( $tab_url( 'edit', [ 'rule' => $rule['id'] ] ) ); ?>"><?php esc_html_e( 'Edit', 'saman-seo' ); ?></a> | </span>
								<span class="duplicate"><a href="<?php echo esc_url( $duplicate_url ); ?>"><?php esc_html_e( 'Duplicate', 'saman-seo' ); ?></a> | </span>
								<span class="toggle"><a href="<?php echo esc_url( $toggle_url ); ?>"><?php echo ( 'active' === $status ) ? esc_html__( 'Deactivate', 'saman-seo' ) : esc_html__( 'Activate', 'saman-seo' ); ?></a> | </span>
								<span class="delete"><a class="submitdelete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'saman-seo' ); ?></a></span>
							</div>
						</td>
						<td>
							<?php if ( $category ) : ?>
								<span class="saman-seo-pill" style="--saman-seo-pill-color: <?php echo esc_attr( $category['color'] ); ?>">
									<?php echo esc_html( $category['name'] ); ?>
								</span>
							<?php else : ?>
								<?php esc_html_e( 'Ã¢â‚¬â€', 'saman-seo' ); ?>
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
								<?php esc_html_e( 'Ã¢â‚¬â€', 'saman-seo' ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php
							if ( 'inherit' === $template_id ) {
								echo esc_html__( 'Inherit', 'saman-seo' );
							} elseif ( $template ) {
								echo esc_html( $template['name'] );
							} else {
								echo esc_html__( 'Custom', 'saman-seo' );
							}
							?>
						</td>
						<td>
							<?php echo esc_html( sprintf( '%1$d Ã‚Â· %2$s', $max_page, ( null === $limit_block ) ? 'Ã¢â‚¬â€' : $limit_block ) ); ?>
						</td>
						<td>
							<span class="saman-seo-status saman-seo-status--<?php echo ( 'active' === $status ) ? 'success' : 'muted'; ?>">
								<?php echo ( 'active' === $status ) ? esc_html__( 'Active', 'saman-seo' ) : esc_html__( 'Inactive', 'saman-seo' ); ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="saman-seo-links__bulk">
				<label>
					<span class="screen-reader-text"><?php esc_html_e( 'Bulk actions', 'saman-seo' ); ?></span>
					<select name="bulk_action" data-bulk-action>
						<?php foreach ( $bulk_actions as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="saman-seo-links__bulk-category" data-bulk-category hidden>
					<span class="screen-reader-text"><?php esc_html_e( 'Select category', 'saman-seo' ); ?></span>
					<select name="bulk_category">
						<option value="__none__"><?php esc_html_e( 'Remove category', 'saman-seo' ); ?></option>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category['id'] ); ?>"><?php echo esc_html( $category['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button type="submit" class="button button-secondary"><?php esc_html_e( 'Apply', 'saman-seo' ); ?></button>
			</div>
		</form>
	<?php endif; ?>
</div>
