<?php
/**
 * Classic meta box layout.
 *
 * @var array $meta
 * @var WP_Post $post
 *
 * @package WPSEOPilot
 */

?>
<div class="wpseopilot-fields">
	<p>
		<label for="wpseopilot_title"><strong><?php esc_html_e( 'Meta title', 'wp-seo-pilot' ); ?></strong></label>
		<input type="text" name="wpseopilot_title" id="wpseopilot_title" class="widefat" value="<?php echo esc_attr( $meta['title'] ); ?>" maxlength="160" />
		<span class="wpseopilot-counter" data-target="wpseopilot_title"></span>
	</p>

	<p>
		<label for="wpseopilot_description"><strong><?php esc_html_e( 'Meta description', 'wp-seo-pilot' ); ?></strong></label>
		<textarea name="wpseopilot_description" id="wpseopilot_description" class="widefat" rows="3" maxlength="320"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
		<span class="wpseopilot-counter" data-target="wpseopilot_description"></span>
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

<?php $suggestions = apply_filters( 'wpseopilot_link_suggestions', [], $post->ID ); ?>
<?php if ( $suggestions ) : ?>
	<div class="wpseopilot-links">
		<h4><?php esc_html_e( 'Internal link suggestions', 'wp-seo-pilot' ); ?></h4>
		<ul>
			<?php foreach ( $suggestions as $suggestion ) : ?>
				<li><a href="<?php echo esc_url( $suggestion['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $suggestion['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
