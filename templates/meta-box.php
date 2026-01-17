<?php
/**
 * Classic meta box layout.
 *
 * @var array   $meta
 * @var WP_Post $post
 * @var bool    $ai_enabled
 * @var array   $seo_score
 *
 * @package Saman\SEO
 */

$SAMAN_SEO_ai_enabled = ! empty( $ai_enabled );
$SAMAN_SEO_score      = is_array( $seo_score ) ? $seo_score : [];
$SAMAN_SEO_score_level = isset( $SAMAN_SEO_score['level'] ) ? sanitize_html_class( $SAMAN_SEO_score['level'] ) : 'low';
$SAMAN_SEO_score_value = isset( $SAMAN_SEO_score['score'] ) ? (int) $SAMAN_SEO_score['score'] : 0;
$SAMAN_SEO_score_label = isset( $SAMAN_SEO_score['label'] ) ? $SAMAN_SEO_score['label'] : __( 'Needs attention', 'saman-seo' );
$SAMAN_SEO_score_summary = isset( $SAMAN_SEO_score['summary'] ) ? $SAMAN_SEO_score['summary'] : __( 'Add content to generate a score.', 'saman-seo' );
?>

<?php if ( $SAMAN_SEO_score ) : ?>
	<div class="saman-seo-score-card" id="saman-seo-score">
		<div class="saman-seo-score-card__header">
			<span class="saman-seo-score-badge <?php echo esc_attr( 'saman-seo-score-badge--' . $SAMAN_SEO_score_level ); ?>">
				<strong><?php echo esc_html( $SAMAN_SEO_score_value ); ?></strong>
				<span>/100</span>
			</span>
			<div>
				<p class="saman-seo-score-card__title"><?php esc_html_e( 'SEO score', 'saman-seo' ); ?></p>
				<p class="saman-seo-score-card__label"><?php echo esc_html( $SAMAN_SEO_score_label ); ?></p>
				<p class="saman-seo-score-card__summary"><?php echo esc_html( $SAMAN_SEO_score_summary ); ?></p>
			</div>
		</div>
		<?php if ( ! empty( $SAMAN_SEO_score['metrics'] ) ) : ?>
			<ul class="saman-seo-score-card__metrics">
				<?php foreach ( $SAMAN_SEO_score['metrics'] as $SAMAN_SEO_metric ) : ?>
					<li class="<?php echo esc_attr( ! empty( $SAMAN_SEO_metric['is_pass'] ) ? 'is-pass' : 'is-issue' ); ?>">
						<span class="saman-seo-score-card__metric-label"><?php echo esc_html( $SAMAN_SEO_metric['label'] ); ?></span>
						<span class="saman-seo-score-card__metric-status"><?php echo esc_html( $SAMAN_SEO_metric['status'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>

<div class="saman-seo-fields">
	<p>
		<label for="SAMAN_SEO_title"><strong><?php esc_html_e( 'Meta title', 'saman-seo' ); ?></strong></label>
		<input type="text" name="SAMAN_SEO_title" id="SAMAN_SEO_title" class="widefat" value="<?php echo esc_attr( $meta['title'] ); ?>" maxlength="160" />
		<span class="saman-seo-counter" data-target="SAMAN_SEO_title"></span>
		<?php if ( $SAMAN_SEO_ai_enabled ) : ?>
			<span class="saman-seo-ai-inline">
				<button type="button" class="button button-secondary saman-seo-ai-button" data-field="title" data-target="#SAMAN_SEO_title" data-post="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Generate title with AI', 'saman-seo' ); ?>
				</button>
				<span class="saman-seo-ai-status" data-ai-status="title"></span>
			</span>
		<?php endif; ?>
	</p>

	<p>
		<label for="SAMAN_SEO_description"><strong><?php esc_html_e( 'Meta description', 'saman-seo' ); ?></strong></label>
		<textarea name="SAMAN_SEO_description" id="SAMAN_SEO_description" class="widefat" rows="3" maxlength="320"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
		<span class="saman-seo-counter" data-target="SAMAN_SEO_description"></span>
		<?php if ( $SAMAN_SEO_ai_enabled ) : ?>
			<span class="saman-seo-ai-inline">
				<button type="button" class="button button-secondary saman-seo-ai-button" data-field="description" data-target="#SAMAN_SEO_description" data-post="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Generate description with AI', 'saman-seo' ); ?>
				</button>
				<span class="saman-seo-ai-status" data-ai-status="description"></span>
			</span>
		<?php endif; ?>
	</p>

	<p>
		<label for="SAMAN_SEO_canonical"><strong><?php esc_html_e( 'Canonical URL override', 'saman-seo' ); ?></strong></label>
		<input type="url" name="SAMAN_SEO_canonical" id="SAMAN_SEO_canonical" class="widefat" value="<?php echo esc_url( $meta['canonical'] ); ?>" />
	</p>

	<p>
		<label>
			<input type="checkbox" name="SAMAN_SEO_noindex" value="1" <?php checked( $meta['noindex'], '1' ); ?> />
			<?php esc_html_e( 'Mark as noindex', 'saman-seo' ); ?>
		</label>
		<br />
		<label>
			<input type="checkbox" name="SAMAN_SEO_nofollow" value="1" <?php checked( $meta['nofollow'], '1' ); ?> />
			<?php esc_html_e( 'Mark as nofollow', 'saman-seo' ); ?>
		</label>
	</p>

	<p>
		<label for="SAMAN_SEO_og_image"><strong><?php esc_html_e( 'Social image override', 'saman-seo' ); ?></strong></label>
		<input type="url" name="SAMAN_SEO_og_image" id="SAMAN_SEO_og_image" class="widefat" value="<?php echo esc_url( $meta['og_image'] ); ?>" />
		<span class="description"><?php esc_html_e( 'Ideal size 1200Ãƒâ€”630. Keep key content centered to avoid crop.', 'saman-seo' ); ?></span>
	</p>
</div>

<div class="saman-seo-preview">
	<h4><?php esc_html_e( 'SERP preview', 'saman-seo' ); ?></h4>
	<div class="saman-seo-serp">
		<p class="saman-seo-serp__title" data-preview="title"><?php echo esc_html( $meta['title'] ?: get_the_title( $post ) ); ?></p>
		<p class="saman-seo-serp__url"><?php echo esc_html( wp_parse_url( get_permalink( $post ), PHP_URL_HOST ) ); ?></p>
		<p class="saman-seo-serp__desc" data-preview="description"><?php echo esc_html( $meta['description'] ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ) ); ?></p>
	</div>

	<h4><?php esc_html_e( 'Social preview', 'saman-seo' ); ?></h4>
	<div class="saman-seo-social">
		<div class="saman-seo-social__image" data-preview="image">
			<?php if ( $meta['og_image'] ) : ?>
				<img src="<?php echo esc_url( $meta['og_image'] ); ?>" alt="" />
			<?php elseif ( has_post_thumbnail( $post ) ) : ?>
				<?php echo wp_kses_post( get_the_post_thumbnail( $post, 'large' ) ); ?>
			<?php endif; ?>
		</div>
		<div>
			<p class="saman-seo-social__title" data-preview="title"><?php echo esc_html( $meta['title'] ?: get_the_title( $post ) ); ?></p>
			<p class="saman-seo-social__desc" data-preview="description"><?php echo esc_html( $meta['description'] ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 20 ) ); ?></p>
		</div>
	</div>
</div>

<?php $SAMAN_SEO_suggestions = apply_filters( 'SAMAN_SEO_link_suggestions', [], $post->ID ); ?>
<?php if ( $SAMAN_SEO_suggestions ) : ?>
	<div class="saman-seo-links">
		<h4><?php esc_html_e( 'Internal link suggestions', 'saman-seo' ); ?></h4>
		<ul>
			<?php foreach ( $SAMAN_SEO_suggestions as $SAMAN_SEO_suggestion ) : ?>
				<li><a href="<?php echo esc_url( $SAMAN_SEO_suggestion['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $SAMAN_SEO_suggestion['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
