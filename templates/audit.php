<?php
/**
 * Audit results table.
 *
 * @var array $issues
 * @var array $stats
 * @var int   $scanned
 * @var array $recommendations
 *
 * @package Saman\SEO
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
	'high'   => __( 'High', 'saman-seo' ),
	'medium' => __( 'Medium', 'saman-seo' ),
	'low'    => __( 'Low', 'saman-seo' ),
];

// Render top bar
\Saman\SEO\Admin_Topbar::render( 'audit' );
?>
<div class="wrap saman-seo-page saman-seo-audit">

	<div class="saman-seo-audit__summary">
		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Snapshot', 'saman-seo' ); ?></h2>
			<ul class="saman-seo-audit__stats">
				<li>
					<strong><?php echo esc_html( number_format_i18n( $scanned ) ); ?></strong>
					<span><?php esc_html_e( 'Posts scanned', 'saman-seo' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( number_format_i18n( $total_issues ) ); ?></strong>
					<span><?php esc_html_e( 'Total issues', 'saman-seo' ); ?></span>
				</li>
				<li>
					<strong><?php echo esc_html( number_format_i18n( $posts_with_issues ) ); ?></strong>
					<span><?php esc_html_e( 'Posts needing attention', 'saman-seo' ); ?></span>
				</li>
			</ul>
		</section>

		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Severity Mix', 'saman-seo' ); ?></h2>
			<div class="saman-seo-audit__bar">
				<?php foreach ( $severity_breakdown as $severity => $count ) : ?>
					<?php
					$percent = $total_issues ? ( $count / max( 1, $total_issues ) ) * 100 : 0;
					?>
					<span class="saman-seo-audit__bar-segment <?php echo esc_attr( $severity_colors[ $severity ] ?? '' ); ?>" style="width: <?php echo esc_attr( $percent ); ?>%"></span>
				<?php endforeach; ?>
			</div>
			<ul class="saman-seo-audit__legend">
				<?php foreach ( $severity_breakdown as $severity => $count ) : ?>
					<li>
						<span class="saman-seo-dot <?php echo esc_attr( $severity_colors[ $severity ] ?? '' ); ?>"></span>
						<?php echo esc_html( sprintf( '%1$s Â· %2$d', $severity_labels[ $severity ] ?? ucfirst( $severity ), $count ) ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Top Issue Types', 'saman-seo' ); ?></h2>
			<ol class="saman-seo-audit__types">
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
								$label = __( 'Missing titles', 'saman-seo' );
								break;
							case 'title_length':
								$label = __( 'Long titles', 'saman-seo' );
								break;
							case 'description_missing':
								$label = __( 'Missing descriptions', 'saman-seo' );
								break;
							case 'missing_alt':
								$label = __( 'Images without alt text', 'saman-seo' );
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
					<li><?php esc_html_e( 'No recurring issues detected.', 'saman-seo' ); ?></li>
				<?php endif; ?>
			</ol>
		</section>
	</div>

	<section class="saman-seo-card">
		<h2><?php esc_html_e( 'Issue Log', 'saman-seo' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'Issue', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'Severity', 'saman-seo' ); ?></th>
					<th><?php esc_html_e( 'Action', 'saman-seo' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $issues ) : ?>
					<?php foreach ( $issues as $issue ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $issue['post_id'] ) ); ?>"><?php echo esc_html( $issue['title'] ); ?></a></td>
							<td><?php echo esc_html( $issue['message'] ); ?></td>
							<td><span class="saman-seo-chip"><?php echo esc_html( ucfirst( $issue['severity'] ) ); ?></span></td>
							<td><?php echo esc_html( $issue['action'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4"><?php esc_html_e( 'No issues detected in the latest scan.', 'saman-seo' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</section>

	<?php if ( ! empty( $recommendations ) ) : ?>
		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Suggested Default Titles, Descriptions & Tags', 'saman-seo' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Use these auto-generated fallbacks whenever editors leave fields blank.', 'saman-seo' ); ?></p>
			<table class="saman-seo-mini-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Post', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Suggested Title', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Suggested Description', 'saman-seo' ); ?></th>
						<th><?php esc_html_e( 'Tags / Keywords', 'saman-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recommendations as $suggestion ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $suggestion['edit_url'] ); ?>"><?php echo esc_html( $suggestion['title'] ); ?></a></td>
							<td><?php echo esc_html( wp_html_excerpt( $suggestion['suggested_title'], 80, 'â€¦' ) ); ?></td>
							<td><?php echo esc_html( wp_html_excerpt( $suggestion['suggested_description'], 120, 'â€¦' ) ); ?></td>
							<td>
								<?php if ( ! empty( $suggestion['suggested_tags'] ) ) : ?>
									<?php foreach ( $suggestion['suggested_tags'] as $tag ) : ?>
										<span class="saman-seo-tag-chip"><?php echo esc_html( $tag ); ?></span>
									<?php endforeach; ?>
								<?php else : ?>
									<span class="saman-seo-muted"><?php esc_html_e( 'No tags detected', 'saman-seo' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>
</div>
