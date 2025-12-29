<?php
/**
 * Schema Markup Sub-Tab Content
 *
 * @package WPSEOPilot
 *
 * Variables expected:
 * - $slug (string): Post type slug
 * - $settings (array): Post type settings
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpseopilot-form-row">
	<?php
	$current_schema = $settings['schema_type'] ?? 'Article';
	$schema_options = [
		'None' => [
			'label' => __( 'None', 'wp-seo-pilot' ),
			'desc'  => __( 'Do not output schema markup.', 'wp-seo-pilot' ),
			'icon'  => 'dashicons-hidden',
			'val'   => '',
		],
		'Article' => [
			'label' => __( 'Article', 'wp-seo-pilot' ),
			'desc'  => __( 'News or blog post content.', 'wp-seo-pilot' ),
			'icon'  => 'dashicons-format-aside',
			'val'   => 'Article',
		],
		'BlogPosting' => [
			'label' => __( 'Blog Posting', 'wp-seo-pilot' ),
			'desc'  => __( 'A post on a blog.', 'wp-seo-pilot' ),
			'icon'  => 'dashicons-welcome-write-blog',
			'val'   => 'BlogPosting',
		],
		'NewsArticle' => [
			'label' => __( 'News Article', 'wp-seo-pilot' ),
			'desc'  => __( 'News story or report.', 'wp-seo-pilot' ),
			'icon'  => 'dashicons-media-document',
			'val'   => 'NewsArticle',
		],
		'WebPage' => [
			'label' => __( 'Web Page', 'wp-seo-pilot' ),
			'desc'  => __( 'Generic web page.', 'wp-seo-pilot' ),
			'icon'  => 'dashicons-admin-page',
			'val'   => 'WebPage',
		],
		'Product' => [
			'label' => __( 'Product', 'wp-seo-pilot' ),
			'desc'  => __( 'Item for sale.', 'wp-seo-pilot' ),
			'icon'  => 'dashicons-cart',
			'val'   => 'Product',
		],
	];
	?>
	<div class="wpseopilot-radio-card-grid">
		<?php foreach ( $schema_options as $key => $opt ) : ?>
			<label class="wpseopilot-radio-card <?php echo ( $current_schema === $opt['val'] ) ? 'is-selected' : ''; ?>">
				<input
					type="radio"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][schema_type]"
					value="<?php echo esc_attr( $opt['val'] ); ?>"
					<?php checked( $current_schema, $opt['val'] ); ?>
				/>
				<span class="wpseopilot-radio-card__icon">
					<span class="dashicons <?php echo esc_attr( $opt['icon'] ); ?>"></span>
				</span>
				<span class="wpseopilot-radio-card__title"><?php echo esc_html( $opt['label'] ); ?></span>
				<span class="wpseopilot-radio-card__desc"><?php echo esc_html( $opt['desc'] ); ?></span>
			</label>
		<?php endforeach; ?>
	</div>
</div>

<div class="wpseopilot-form-row">
	<p class="wpseopilot-info-notice">
		<span class="dashicons dashicons-info"></span>
		<?php esc_html_e( 'Additional schema configuration options will be available in future updates.', 'wp-seo-pilot' ); ?>
	</p>
</div>
