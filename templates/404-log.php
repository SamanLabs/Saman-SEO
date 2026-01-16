<?php
/**
 * 404 log report.
 *
 * @var array $rows
 *
 * @package WPSEOPilot
 */

?>
<?php
// Render top bar
\WPSEOPilot\Admin_Topbar::render( '404-log' );
?>
<div class="wrap wpseopilot-page">

	<form method="get" class="wpseopilot-404-controls">
		<input type="hidden" name="page" value="wpseopilot-404" />
		<label for="wpseopilot-404-sort">
			<?php esc_html_e( 'Sort by', 'saman-labs-seo' ); ?>
			<select id="wpseopilot-404-sort" name="sort">
				<option value="recent" <?php selected( $sort, 'recent' ); ?>><?php esc_html_e( 'Most recent (Date & Time)', 'saman-labs-seo' ); ?></option>
				<option value="top" <?php selected( $sort, 'top' ); ?>><?php esc_html_e( 'Top hits', 'saman-labs-seo' ); ?></option>
			</select>
		</label>
		<label for="wpseopilot-404-per-page" style="margin-left:1em;">
			<?php esc_html_e( 'Rows per page', 'saman-labs-seo' ); ?>
			<select id="wpseopilot-404-per-page" name="per_page">
				<?php foreach ( [ 25, 50, 100, 200 ] as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $per_page, $option ); ?>><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label for="wpseopilot-404-hide-spam" style="margin-left:1em;">
			<input id="wpseopilot-404-hide-spam" type="checkbox" name="hide_spam" value="1" <?php checked( $hide_spam ); ?> />
			<?php esc_html_e( 'Hide spammy extensions', 'saman-labs-seo' ); ?>
		</label>
		<label for="wpseopilot-404-hide-images" style="margin-left:1em;">
			<input id="wpseopilot-404-hide-images" type="checkbox" name="hide_images" value="1" <?php checked( $hide_images ); ?> />
			<?php esc_html_e( 'Hide image extensions', 'saman-labs-seo' ); ?>
		</label>
		<button type="submit" class="button button-secondary" style="margin-left:1em;">
			<?php esc_html_e( 'Apply', 'saman-labs-seo' ); ?>
		</button>
	</form>

	<p>
		<?php
		printf(
			/* translators: 1: total log entries, 2: current page, 3: total pages */
			esc_html__( '%1$s entries logged. Page %2$s of %3$s.', 'saman-labs-seo' ),
			number_format_i18n( $total_count ),
			number_format_i18n( $page ),
			number_format_i18n( $total_pages )
		);
		?>
	</p>

		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Target URL', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Hits', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Date & Time', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'User Device', 'saman-labs-seo' ); ?></th>
					<th><?php esc_html_e( 'Quick action', 'saman-labs-seo' ); ?></th>
				</tr>
			</thead>
		<tbody>
			<?php if ( $rows ) : ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td>
							<?php echo esc_html( $row->request_uri ); ?>
							<?php if ( ! empty( $row->redirect_exists ) ) : ?>
								<span class="wpseopilot-404-tag" style="margin-left:0.5em;display:inline-block;padding:0 6px;border-radius:10px;background:#e6f2ff;color:#0b57d0;font-size:11px;line-height:18px;">
									<?php esc_html_e( 'Redirect exists', 'saman-labs-seo' ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row->hits ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row->last_seen ) ); ?></td>
						<td><?php echo esc_html( $row->device_label ?: __( 'Unknown device', 'saman-labs-seo' ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-redirects&prefill=' . rawurlencode( $row->request_uri ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Create redirect', 'saman-labs-seo' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No 404s logged yet.', 'saman-labs-seo' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<?php
		$filter_args = [
			'sort'     => $sort,
			'per_page' => $per_page,
		];
		if ( $hide_spam ) {
			$filter_args['hide_spam'] = 1;
		}
		if ( $hide_images ) {
			$filter_args['hide_images'] = 1;
		}

		$prev_link = ( $page > 1 ) ? add_query_arg(
			array_merge(
				$filter_args,
				[ 'paged' => $page - 1 ]
			),
			$base_url
		) : '';
		$next_link = ( $page < $total_pages ) ? add_query_arg(
			array_merge(
				$filter_args,
				[ 'paged' => $page + 1 ]
			),
			$base_url
		) : '';
		?>
		<div class="tablenav-pages" style="margin-top:1em;">
			<span class="displaying-num"><?php echo esc_html( sprintf( _n( '%s item', '%s items', $total_count, 'saman-labs-seo' ), number_format_i18n( $total_count ) ) ); ?></span>
			<span class="pagination-links">
				<?php if ( $prev_link ) : ?>
					<a class="prev-page" href="<?php echo esc_url( $prev_link ); ?>">&lsaquo; <?php esc_html_e( 'Previous', 'saman-labs-seo' ); ?></a>
				<?php else : ?>
					<span class="tablenav-pages-navspan">&lsaquo; <?php esc_html_e( 'Previous', 'saman-labs-seo' ); ?></span>
				<?php endif; ?>

				<span class="paging-input">
					<?php echo esc_html( number_format_i18n( $page ) ); ?>
					<?php esc_html_e( 'of', 'saman-labs-seo' ); ?>
					<span class="total-pages"><?php echo esc_html( number_format_i18n( $total_pages ) ); ?></span>
				</span>

				<?php if ( $next_link ) : ?>
					<a class="next-page" href="<?php echo esc_url( $next_link ); ?>"><?php esc_html_e( 'Next', 'saman-labs-seo' ); ?> &rsaquo;</a>
				<?php else : ?>
					<span class="tablenav-pages-navspan"><?php esc_html_e( 'Next', 'saman-labs-seo' ); ?> &rsaquo;</span>
				<?php endif; ?>
			</span>
		</div>
	<?php endif; ?>
</div>
