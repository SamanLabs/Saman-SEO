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

<div class="samanlabs-seo-form-row">
	<?php
	$current_schema = $settings['schema_type'] ?? 'Article';
	$schema_options = [
		'None' => [
			'label' => __( 'None', 'saman-labs-seo' ),
			'desc'  => __( 'Do not output schema markup.', 'saman-labs-seo' ),
			'icon'  => 'dashicons-hidden',
			'val'   => '',
		],
		'Article' => [
			'label' => __( 'Article', 'saman-labs-seo' ),
			'desc'  => __( 'News or blog post content.', 'saman-labs-seo' ),
			'icon'  => 'dashicons-format-aside',
			'val'   => 'Article',
		],
		'BlogPosting' => [
			'label' => __( 'Blog Posting', 'saman-labs-seo' ),
			'desc'  => __( 'A post on a blog.', 'saman-labs-seo' ),
			'icon'  => 'dashicons-welcome-write-blog',
			'val'   => 'BlogPosting',
		],
		'NewsArticle' => [
			'label' => __( 'News Article', 'saman-labs-seo' ),
			'desc'  => __( 'News story or report.', 'saman-labs-seo' ),
			'icon'  => 'dashicons-media-document',
			'val'   => 'NewsArticle',
		],
		'WebPage' => [
			'label' => __( 'Web Page', 'saman-labs-seo' ),
			'desc'  => __( 'Generic web page.', 'saman-labs-seo' ),
			'icon'  => 'dashicons-admin-page',
			'val'   => 'WebPage',
		],
		'Product' => [
			'label' => __( 'Product', 'saman-labs-seo' ),
			'desc'  => __( 'Item for sale.', 'saman-labs-seo' ),
			'icon'  => 'dashicons-cart',
			'val'   => 'Product',
		],
	];
	?>
	<div class="samanlabs-seo-radio-card-grid">
		<?php foreach ( $schema_options as $key => $opt ) : ?>
			<label class="samanlabs-seo-radio-card <?php echo ( $current_schema === $opt['val'] ) ? 'is-selected' : ''; ?>">
				<input
					type="radio"
					name="samanlabs_seo_post_type_defaults[<?php echo esc_attr( $slug ); ?>][schema_type]"
					value="<?php echo esc_attr( $opt['val'] ); ?>"
					<?php checked( $current_schema, $opt['val'] ); ?>
				/>
				<span class="samanlabs-seo-radio-card__icon">
					<span class="dashicons <?php echo esc_attr( $opt['icon'] ); ?>"></span>
				</span>
				<span class="samanlabs-seo-radio-card__title"><?php echo esc_html( $opt['label'] ); ?></span>
				<span class="samanlabs-seo-radio-card__desc"><?php echo esc_html( $opt['desc'] ); ?></span>
			</label>
		<?php endforeach; ?>
	</div>
</div>

<div class="samanlabs-seo-form-row">
	<p class="samanlabs-seo-info-notice">
		<span class="dashicons dashicons-info"></span>
		<?php esc_html_e( 'Additional schema configuration options will be available in future updates.', 'saman-labs-seo' ); ?>
	</p>
</div>
