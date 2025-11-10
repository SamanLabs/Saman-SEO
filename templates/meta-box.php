<?php
/**
 * Classic meta box layout.
 *
 * @var array   $meta
 * @var WP_Post $post
 * @var bool    $ai_enabled
 * @var array   $seo_score
 *
 * @package WPSEOPilot
 */

$wpseopilot_ai_enabled = ! empty( $ai_enabled );
$wpseopilot_score      = is_array( $seo_score ) ? $seo_score : [];
$wpseopilot_score_level = isset( $wpseopilot_score['level'] ) ? sanitize_html_class( $wpseopilot_score['level'] ) : 'low';
$wpseopilot_score_value = isset( $wpseopilot_score['score'] ) ? (int) $wpseopilot_score['score'] : 0;
$wpseopilot_score_label = isset( $wpseopilot_score['label'] ) ? $wpseopilot_score['label'] : __( 'Needs attention', 'wp-seo-pilot' );
$wpseopilot_score_summary = isset( $wpseopilot_score['summary'] ) ? $wpseopilot_score['summary'] : __( 'Add content to generate a score.', 'wp-seo-pilot' );
?>

<?php if ( $wpseopilot_score ) : ?>
	<div class="wpseopilot-score-card" id="wpseopilot-score">
		<div class="wpseopilot-score-card__header">
			<span class="wpseopilot-score-badge <?php echo esc_attr( 'wpseopilot-score-badge--' . $wpseopilot_score_level ); ?>">
				<strong><?php echo esc_html( $wpseopilot_score_value ); ?></strong>
				<span>/100</span>
			</span>
			<div>
				<p class="wpseopilot-score-card__title"><?php esc_html_e( 'SEO score', 'wp-seo-pilot' ); ?></p>
				<p class="wpseopilot-score-card__label"><?php echo esc_html( $wpseopilot_score_label ); ?></p>
				<p class="wpseopilot-score-card__summary"><?php echo esc_html( $wpseopilot_score_summary ); ?></p>
			</div>
		</div>
		<?php if ( ! empty( $wpseopilot_score['metrics'] ) ) : ?>
			<ul class="wpseopilot-score-card__metrics">
				<?php foreach ( $wpseopilot_score['metrics'] as $wpseopilot_metric ) : ?>
					<li class="<?php echo esc_attr( ! empty( $wpseopilot_metric['is_pass'] ) ? 'is-pass' : 'is-issue' ); ?>">
						<span class="wpseopilot-score-card__metric-label"><?php echo esc_html( $wpseopilot_metric['label'] ); ?></span>
						<span class="wpseopilot-score-card__metric-status"><?php echo esc_html( $wpseopilot_metric['status'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>

<div class="wpseopilot-fields">
	<p>
		<label for="wpseopilot_title"><strong><?php esc_html_e( 'Meta title', 'wp-seo-pilot' ); ?></strong></label>
		<input type="text" name="wpseopilot_title" id="wpseopilot_title" class="widefat" value="<?php echo esc_attr( $meta['title'] ); ?>" maxlength="160" />
		<span class="wpseopilot-counter" data-target="wpseopilot_title"></span>
		<?php if ( $wpseopilot_ai_enabled ) : ?>
			<span class="wpseopilot-ai-inline">
				<button type="button" class="button button-secondary wpseopilot-ai-button" data-field="title" data-target="#wpseopilot_title" data-post="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Generate title with AI', 'wp-seo-pilot' ); ?>
				</button>
				<span class="wpseopilot-ai-status" data-ai-status="title"></span>
			</span>
		<?php endif; ?>
	</p>

	<p>
		<label for="wpseopilot_description"><strong><?php esc_html_e( 'Meta description', 'wp-seo-pilot' ); ?></strong></label>
		<textarea name="wpseopilot_description" id="wpseopilot_description" class="widefat" rows="3" maxlength="320"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
		<span class="wpseopilot-counter" data-target="wpseopilot_description"></span>
		<?php if ( $wpseopilot_ai_enabled ) : ?>
			<span class="wpseopilot-ai-inline">
				<button type="button" class="button button-secondary wpseopilot-ai-button" data-field="description" data-target="#wpseopilot_description" data-post="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Generate description with AI', 'wp-seo-pilot' ); ?>
				</button>
				<span class="wpseopilot-ai-status" data-ai-status="description"></span>
			</span>
		<?php endif; ?>
	</p>

	<p>
		<label for="wpseopilot_canonical"><strong><?php esc_html_e( 'Canonical URL override', 'wp-seo-pilot' ); ?></strong></label>
		<input type="url" name="wpseopilot_canonical" id="wpseopilot_canonical" class="widefat" value="<?php echo esc_url( $meta['canonical'] ); ?>" />
	</p>

	<p>
		<label>
			<input type="checkbox" name="wpseopilot_noindex" value="1" <?php checked( $meta['noindex'], '1' ); ?> />
			<?php esc_html_e( 'Mark as noindex', 'wp-seo-pilot' ); ?>
		</label>
		<br />
		<label>
			<input type="checkbox" name="wpseopilot_nofollow" value="1" <?php checked( $meta['nofollow'], '1' ); ?> />
			<?php esc_html_e( 'Mark as nofollow', 'wp-seo-pilot' ); ?>
		</label>
	</p>

	<p>
		<label for="wpseopilot_og_image"><strong><?php esc_html_e( 'Social image override', 'wp-seo-pilot' ); ?></strong></label>
		<input type="url" name="wpseopilot_og_image" id="wpseopilot_og_image" class="widefat" value="<?php echo esc_url( $meta['og_image'] ); ?>" />
		<span class="description"><?php esc_html_e( 'Ideal size 1200×630. Keep key content centered to avoid crop.', 'wp-seo-pilot' ); ?></span>
	</p>
</div>

<div class="wpseopilot-preview">
	<h4><?php esc_html_e( 'SERP preview', 'wp-seo-pilot' ); ?></h4>
	<div class="wpseopilot-serp">
		<p class="wpseopilot-serp__title" data-preview="title"><?php echo esc_html( $meta['title'] ?: get_the_title( $post ) ); ?></p>
		<p class="wpseopilot-serp__url"><?php echo esc_html( wp_parse_url( get_permalink( $post ), PHP_URL_HOST ) ); ?></p>
		<p class="wpseopilot-serp__desc" data-preview="description"><?php echo esc_html( $meta['description'] ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ) ); ?></p>
	</div>

	<h4><?php esc_html_e( 'Social preview', 'wp-seo-pilot' ); ?></h4>
	<div class="wpseopilot-social">
		<div class="wpseopilot-social__image" data-preview="image">
			<?php if ( $meta['og_image'] ) : ?>
				<img src="<?php echo esc_url( $meta['og_image'] ); ?>" alt="" />
			<?php elseif ( has_post_thumbnail( $post ) ) : ?>
				<?php echo wp_kses_post( get_the_post_thumbnail( $post, 'large' ) ); ?>
			<?php endif; ?>
		</div>
		<div>
			<p class="wpseopilot-social__title" data-preview="title"><?php echo esc_html( $meta['title'] ?: get_the_title( $post ) ); ?></p>
			<p class="wpseopilot-social__desc" data-preview="description"><?php echo esc_html( $meta['description'] ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 20 ) ); ?></p>
		</div>
	</div>
</div>

<?php $wpseopilot_suggestions = apply_filters( 'wpseopilot_link_suggestions', [], $post->ID ); ?>
<?php if ( $wpseopilot_suggestions ) : ?>
	<div class="wpseopilot-links">
		<h4><?php esc_html_e( 'Internal link suggestions', 'wp-seo-pilot' ); ?></h4>
		<ul>
			<?php foreach ( $wpseopilot_suggestions as $wpseopilot_suggestion ) : ?>
				<li><a href="<?php echo esc_url( $wpseopilot_suggestion['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $wpseopilot_suggestion['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
