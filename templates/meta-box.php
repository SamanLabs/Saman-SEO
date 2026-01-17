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

$samanlabs_seo_ai_enabled = ! empty( $ai_enabled );
$samanlabs_seo_score      = is_array( $seo_score ) ? $seo_score : [];
$samanlabs_seo_score_level = isset( $samanlabs_seo_score['level'] ) ? sanitize_html_class( $samanlabs_seo_score['level'] ) : 'low';
$samanlabs_seo_score_value = isset( $samanlabs_seo_score['score'] ) ? (int) $samanlabs_seo_score['score'] : 0;
$samanlabs_seo_score_label = isset( $samanlabs_seo_score['label'] ) ? $samanlabs_seo_score['label'] : __( 'Needs attention', 'saman-labs-seo' );
$samanlabs_seo_score_summary = isset( $samanlabs_seo_score['summary'] ) ? $samanlabs_seo_score['summary'] : __( 'Add content to generate a score.', 'saman-labs-seo' );
?>

<?php if ( $samanlabs_seo_score ) : ?>
	<div class="samanlabs-seo-score-card" id="samanlabs-seo-score">
		<div class="samanlabs-seo-score-card__header">
			<span class="samanlabs-seo-score-badge <?php echo esc_attr( 'samanlabs-seo-score-badge--' . $samanlabs_seo_score_level ); ?>">
				<strong><?php echo esc_html( $samanlabs_seo_score_value ); ?></strong>
				<span>/100</span>
			</span>
			<div>
				<p class="samanlabs-seo-score-card__title"><?php esc_html_e( 'SEO score', 'saman-labs-seo' ); ?></p>
				<p class="samanlabs-seo-score-card__label"><?php echo esc_html( $samanlabs_seo_score_label ); ?></p>
				<p class="samanlabs-seo-score-card__summary"><?php echo esc_html( $samanlabs_seo_score_summary ); ?></p>
			</div>
		</div>
		<?php if ( ! empty( $samanlabs_seo_score['metrics'] ) ) : ?>
			<ul class="samanlabs-seo-score-card__metrics">
				<?php foreach ( $samanlabs_seo_score['metrics'] as $samanlabs_seo_metric ) : ?>
					<li class="<?php echo esc_attr( ! empty( $samanlabs_seo_metric['is_pass'] ) ? 'is-pass' : 'is-issue' ); ?>">
						<span class="samanlabs-seo-score-card__metric-label"><?php echo esc_html( $samanlabs_seo_metric['label'] ); ?></span>
						<span class="samanlabs-seo-score-card__metric-status"><?php echo esc_html( $samanlabs_seo_metric['status'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>

<div class="samanlabs-seo-fields">
	<p>
		<label for="samanlabs_seo_title"><strong><?php esc_html_e( 'Meta title', 'saman-labs-seo' ); ?></strong></label>
		<input type="text" name="samanlabs_seo_title" id="samanlabs_seo_title" class="widefat" value="<?php echo esc_attr( $meta['title'] ); ?>" maxlength="160" />
		<span class="samanlabs-seo-counter" data-target="samanlabs_seo_title"></span>
		<?php if ( $samanlabs_seo_ai_enabled ) : ?>
			<span class="samanlabs-seo-ai-inline">
				<button type="button" class="button button-secondary samanlabs-seo-ai-button" data-field="title" data-target="#samanlabs_seo_title" data-post="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Generate title with AI', 'saman-labs-seo' ); ?>
				</button>
				<span class="samanlabs-seo-ai-status" data-ai-status="title"></span>
			</span>
		<?php endif; ?>
	</p>

	<p>
		<label for="samanlabs_seo_description"><strong><?php esc_html_e( 'Meta description', 'saman-labs-seo' ); ?></strong></label>
		<textarea name="samanlabs_seo_description" id="samanlabs_seo_description" class="widefat" rows="3" maxlength="320"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
		<span class="samanlabs-seo-counter" data-target="samanlabs_seo_description"></span>
		<?php if ( $samanlabs_seo_ai_enabled ) : ?>
			<span class="samanlabs-seo-ai-inline">
				<button type="button" class="button button-secondary samanlabs-seo-ai-button" data-field="description" data-target="#samanlabs_seo_description" data-post="<?php echo esc_attr( $post->ID ); ?>">
					<?php esc_html_e( 'Generate description with AI', 'saman-labs-seo' ); ?>
				</button>
				<span class="samanlabs-seo-ai-status" data-ai-status="description"></span>
			</span>
		<?php endif; ?>
	</p>

	<p>
		<label for="samanlabs_seo_canonical"><strong><?php esc_html_e( 'Canonical URL override', 'saman-labs-seo' ); ?></strong></label>
		<input type="url" name="samanlabs_seo_canonical" id="samanlabs_seo_canonical" class="widefat" value="<?php echo esc_url( $meta['canonical'] ); ?>" />
	</p>

	<p>
		<label>
			<input type="checkbox" name="samanlabs_seo_noindex" value="1" <?php checked( $meta['noindex'], '1' ); ?> />
			<?php esc_html_e( 'Mark as noindex', 'saman-labs-seo' ); ?>
		</label>
		<br />
		<label>
			<input type="checkbox" name="samanlabs_seo_nofollow" value="1" <?php checked( $meta['nofollow'], '1' ); ?> />
			<?php esc_html_e( 'Mark as nofollow', 'saman-labs-seo' ); ?>
		</label>
	</p>

	<p>
		<label for="samanlabs_seo_og_image"><strong><?php esc_html_e( 'Social image override', 'saman-labs-seo' ); ?></strong></label>
		<input type="url" name="samanlabs_seo_og_image" id="samanlabs_seo_og_image" class="widefat" value="<?php echo esc_url( $meta['og_image'] ); ?>" />
		<span class="description"><?php esc_html_e( 'Ideal size 1200×630. Keep key content centered to avoid crop.', 'saman-labs-seo' ); ?></span>
	</p>
</div>

<div class="samanlabs-seo-preview">
	<h4><?php esc_html_e( 'SERP preview', 'saman-labs-seo' ); ?></h4>
	<div class="samanlabs-seo-serp">
		<p class="samanlabs-seo-serp__title" data-preview="title"><?php echo esc_html( $meta['title'] ?: get_the_title( $post ) ); ?></p>
		<p class="samanlabs-seo-serp__url"><?php echo esc_html( wp_parse_url( get_permalink( $post ), PHP_URL_HOST ) ); ?></p>
		<p class="samanlabs-seo-serp__desc" data-preview="description"><?php echo esc_html( $meta['description'] ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ) ); ?></p>
	</div>

	<h4><?php esc_html_e( 'Social preview', 'saman-labs-seo' ); ?></h4>
	<div class="samanlabs-seo-social">
		<div class="samanlabs-seo-social__image" data-preview="image">
			<?php if ( $meta['og_image'] ) : ?>
				<img src="<?php echo esc_url( $meta['og_image'] ); ?>" alt="" />
			<?php elseif ( has_post_thumbnail( $post ) ) : ?>
				<?php echo wp_kses_post( get_the_post_thumbnail( $post, 'large' ) ); ?>
			<?php endif; ?>
		</div>
		<div>
			<p class="samanlabs-seo-social__title" data-preview="title"><?php echo esc_html( $meta['title'] ?: get_the_title( $post ) ); ?></p>
			<p class="samanlabs-seo-social__desc" data-preview="description"><?php echo esc_html( $meta['description'] ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 20 ) ); ?></p>
		</div>
	</div>
</div>

<?php $samanlabs_seo_suggestions = apply_filters( 'samanlabs_seo_link_suggestions', [], $post->ID ); ?>
<?php if ( $samanlabs_seo_suggestions ) : ?>
	<div class="samanlabs-seo-links">
		<h4><?php esc_html_e( 'Internal link suggestions', 'saman-labs-seo' ); ?></h4>
		<ul>
			<?php foreach ( $samanlabs_seo_suggestions as $samanlabs_seo_suggestion ) : ?>
				<li><a href="<?php echo esc_url( $samanlabs_seo_suggestion['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $samanlabs_seo_suggestion['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
