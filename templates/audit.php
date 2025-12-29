<?php
/**
 * Audit results table.
 *
 * @var array $issues
 * @var array $stats
 * @var int   $scanned
 * @var array $recommendations
 *
 * @package WPSEOPilot
 */

$total_issues       = $stats['total'] ?? count( $issues );
$severity_breakdown = $stats['severity'] ?? [];
$type_breakdown     = $stats['types'] ?? [];
$posts_with_issues  = $stats['posts_with_issues'] ?? 0;
$scanned            = $scanned ?? 0;
$severity_colors    = [
	'high'   => 'is-high',
	'medium' => 'is-medium',
	'low'    => 'is-low',
];

$severity_labels = [
	'high'   => __( 'High', 'wp-seo-pilot' ),
	'medium' => __( 'Medium', 'wp-seo-pilot' ),
	'low'    => __( 'Low', 'wp-seo-pilot' ),
];

// Render top bar
\WPSEOPilot\Admin_Topbar::render( 'audit' );
?>
<div class="wrap wpseopilot-page wpseopilot-audit">

	<div class="wpseopilot-audit__summary">
		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Snapshot', 'wp-seo-pilot' ); ?></h2>
			<ul class="wpseopilot-audit__stats">
				<li>
					<strong><?php echo esc_html( number_format_i18n( $scanned ) ); ?></strong>
					<span><?php esc_html_e( 'Posts scanned', 'wp-seo-pilot' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( number_format_i18n( $total_issues ) ); ?></strong>
					<span><?php esc_html_e( 'Total issues', 'wp-seo-pilot' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( number_format_i18n( $posts_with_issues ) ); ?></strong>
					<span><?php esc_html_e( 'Posts needing attention', 'wp-seo-pilot' ); ?></span>
				</li>
			</ul>
		</section>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Severity Mix', 'wp-seo-pilot' ); ?></h2>
			<div class="wpseopilot-audit__bar">
				<?php foreach ( $severity_breakdown as $severity => $count ) : ?>
					<?php
					$percent = $total_issues ? ( $count / max( 1, $total_issues ) ) * 100 : 0;
					?>
					<span class="wpseopilot-audit__bar-segment <?php echo esc_attr( $severity_colors[ $severity ] ?? '' ); ?>" style="width: <?php echo esc_attr( $percent ); ?>%"></span>
				<?php endforeach; ?>
			</div>
			<ul class="wpseopilot-audit__legend">
				<?php foreach ( $severity_breakdown as $severity => $count ) : ?>
					<li>
						<span class="wpseopilot-dot <?php echo esc_attr( $severity_colors[ $severity ] ?? '' ); ?>"></span>
						<?php echo esc_html( sprintf( '%1$s · %2$d', $severity_labels[ $severity ] ?? ucfirst( $severity ), $count ) ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Top Issue Types', 'wp-seo-pilot' ); ?></h2>
			<ol class="wpseopilot-audit__types">
				<?php if ( $type_breakdown ) : ?>
					<?php $count = 0; ?>
					<?php foreach ( $type_breakdown as $type => $type_count ) : ?>
						<?php
						++$count;
						if ( $count > 5 ) {
							break;
						}
						$label = '';
						switch ( $type ) {
							case 'title_missing':
								$label = __( 'Missing titles', 'wp-seo-pilot' );
								break;
							case 'title_length':
								$label = __( 'Long titles', 'wp-seo-pilot' );
								break;
							case 'description_missing':
								$label = __( 'Missing descriptions', 'wp-seo-pilot' );
								break;
							case 'missing_alt':
								$label = __( 'Images without alt text', 'wp-seo-pilot' );
								break;
							default:
								$label = ucfirst( str_replace( '_', ' ', $type ) );
								break;
						}
						?>
						<li>
							<span><?php echo esc_html( $label ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $type_count ) ); ?></strong>
						</li>
					<?php endforeach; ?>
				<?php else : ?>
					<li><?php esc_html_e( 'No recurring issues detected.', 'wp-seo-pilot' ); ?></li>
				<?php endif; ?>
			</ol>
		</section>
	</div>

	<section class="wpseopilot-card">
		<h2><?php esc_html_e( 'Issue Log', 'wp-seo-pilot' ); ?></h2>
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
	</section>

	<?php if ( ! empty( $recommendations ) ) : ?>
		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Suggested Default Titles, Descriptions & Tags', 'wp-seo-pilot' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Use these auto-generated fallbacks whenever editors leave fields blank.', 'wp-seo-pilot' ); ?></p>
			<table class="wpseopilot-mini-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Post', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Suggested Title', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Suggested Description', 'wp-seo-pilot' ); ?></th>
						<th><?php esc_html_e( 'Tags / Keywords', 'wp-seo-pilot' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recommendations as $suggestion ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $suggestion['edit_url'] ); ?>"><?php echo esc_html( $suggestion['title'] ); ?></a></td>
							<td><?php echo esc_html( wp_html_excerpt( $suggestion['suggested_title'], 80, '…' ) ); ?></td>
							<td><?php echo esc_html( wp_html_excerpt( $suggestion['suggested_description'], 120, '…' ) ); ?></td>
							<td>
								<?php if ( ! empty( $suggestion['suggested_tags'] ) ) : ?>
									<?php foreach ( $suggestion['suggested_tags'] as $tag ) : ?>
										<span class="wpseopilot-tag-chip"><?php echo esc_html( $tag ); ?></span>
									<?php endforeach; ?>
								<?php else : ?>
									<span class="wpseopilot-muted"><?php esc_html_e( 'No tags detected', 'wp-seo-pilot' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>
</div>
