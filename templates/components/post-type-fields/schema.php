<?php
/**
 * Schema Markup Sub-Tab Content
 *
 * @package Saman\SEO
 *
 * Variables expected:
 * - $slug (string): Post type slug
 * - $settings (array): Post type settings
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="saman-seo-form-row">
	<?php
	$current_schema = $settings['schema_type'] ?? 'Article';
	$schema_options = [
		'None' => [
			'label' => __( 'None', 'saman-seo' ),
			'desc'  => __( 'Do not output schema markup.', 'saman-seo' ),
			'icon'  => 'dashicons-hidden',
			'val'   => '',
		],
		'Article' => [
			'label' => __( 'Article', 'saman-seo' ),
			'desc'  => __( 'News or blog post content.', 'saman-seo' ),
			'icon'  => 'dashicons-format-aside',
			'val'   => 'Article',
		],
		'BlogPosting' => [
			'label' => __( 'Blog Posting', 'saman-seo' ),
			'desc'  => __( 'A post on a blog.', 'saman-seo' ),
			'icon'  => 'dashicons-welcome-write-blog',
			'val'   => 'BlogPosting',
		],
		'NewsArticle' => [
			'label' => __( 'News Article', 'saman-seo' ),
			'desc'  => __( 'News story or report.', 'saman-seo' ),
			'icon'  => 'dashicons-media-document',
			'val'   => 'NewsArticle',
		],
		'WebPage' => [
			'label' => __( 'Web Page', 'saman-seo' ),
			'desc'  => __( 'Generic web page.', 'saman-seo' ),
			'icon'  => 'dashicons-admin-page',
			'val'   => 'WebPage',
		],
		'Product' => [
			'label' => __( 'Product', 'saman-seo' ),
			'desc'  => __( 'Item for sale.', 'saman-seo' ),
			'icon'  => 'dashicons-cart',
			'val'   => 'Product',
		],
	];
	?>
	<div class="saman-seo-radio-card-grid">
		<?php foreach ( $schema_options as $key => $opt ) : ?>
			<label class="saman-seo-radio-card <?php echo ( $current_schema === $opt['val'] ) ? 'is-selected' : ''; ?>">
				<input
					type="radio"
					name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][schema_type]"
					value="<?php echo esc_attr( $opt['val'] ); ?>"
					<?php checked( $current_schema, $opt['val'] ); ?>
				/>
				<span class="saman-seo-radio-card__icon">
					<span class="dashicons <?php echo esc_attr( $opt['icon'] ); ?>"></span>
				</span>
				<span class="saman-seo-radio-card__title"><?php echo esc_html( $opt['label'] ); ?></span>
				<span class="saman-seo-radio-card__desc"><?php echo esc_html( $opt['desc'] ); ?></span>
			</label>
		<?php endforeach; ?>
	</div>
</div>

<div class="saman-seo-form-row">
	<p class="saman-seo-info-notice">
		<span class="dashicons dashicons-info"></span>
		<?php esc_html_e( 'Additional schema configuration options will be available in future updates.', 'saman-seo' ); ?>
	</p>
</div>
