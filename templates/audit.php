<?php
/**
 * Audit results table.
 *
 * @var array $issues
 *
 * @package WPSEOPilot
 */

?>
<div class="wrap wpseopilot-audit">
	<h1><?php esc_html_e( 'SEO Audit', 'wp-seo-pilot' ); ?></h1>
	<p><?php esc_html_e( 'Automated checks for missing metadata, alt text, and length issues.', 'wp-seo-pilot' ); ?></p>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Post', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Issue', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Severity', 'wp-seo-pilot' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wp-seo-pilot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( $issues ) : ?>
				<?php foreach ( $issues as $issue ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_post_link( $issue['post_id'] ) ); ?>"><?php echo esc_html( $issue['title'] ); ?></a></td>
						<td><?php echo esc_html( $issue['message'] ); ?></td>
						<td><span class="wpseopilot-chip"><?php echo esc_html( ucfirst( $issue['severity'] ) ); ?></span></td>
						<td><?php echo esc_html( $issue['action'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="4"><?php esc_html_e( 'No issues detected in the latest scan.', 'wp-seo-pilot' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
