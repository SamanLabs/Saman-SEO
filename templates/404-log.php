<?php
/**
 * 404 log report.
 *
 * @var array $rows
 *
 * @package WPSEOPilot
 */

?>
<div class="wrap">
	<h1><?php esc_html_e( '404 Monitor', 'wp-seo-pilot' ); ?></h1>
	<p><?php esc_html_e( 'Top missing URLs, sorted by hits.', 'wp-seo-pilot' ); ?></p>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'URL', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Hits', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Last seen', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Referrer hash', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Quick action', 'wp-seo-pilot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $rows ) : ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->request_uri ); ?></td>
						<td><?php echo esc_html( $row->hits ); ?></td>
						<td><?php echo esc_html( $row->last_seen ); ?></td>
						<td><code><?php echo esc_html( substr( $row->referrer_hash, 0, 12 ) ); ?></code></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-redirects&prefill=' . rawurlencode( $row->request_uri ) ) ); ?>" class="button button-small"><?php esc_html_e( 'Create redirect', 'wp-seo-pilot' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="4"><?php esc_html_e( 'No 404s logged yet.', 'wp-seo-pilot' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
