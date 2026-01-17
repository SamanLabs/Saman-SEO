<?php
/**
 * Google Search Preview Component
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

$preview_title = $preview_title ?? get_bloginfo( 'name' );
$preview_url = $preview_url ?? home_url();
$preview_description = $preview_description ?? get_bloginfo( 'description' );
$preview_domain = parse_url( home_url(), PHP_URL_HOST );
?>

<div class="saman-seo-google-preview">
	<div class="saman-seo-google-preview__header">
		<div style="display: flex; align-items: center; gap: 8px;">
			<span class="dashicons dashicons-search"></span>
			<span><?php esc_html_e( 'Google Search Preview', 'saman-seo' ); ?></span>
		</div>
		<button type="button" class="button button-small saman-seo-preview-source-toggle" style="margin-left: auto;">
			<?php esc_html_e( 'Change Source', 'saman-seo' ); ?>
		</button>
	</div>
	
	<!-- Preview Source Control -->
	<div class="saman-seo-preview-source-panel" style="display: none; padding: 10px 15px; background: #f0f0f1; border-bottom: 1px solid #dfe3ec;">
		<div style="display: flex; gap: 8px; align-items: center;">
			<label style="font-size: 12px; font-weight: 500;"><?php esc_html_e( 'Preview Data From:', 'saman-seo' ); ?></label>
			<input type="number" class="small-text saman-seo-preview-object-id-input" placeholder="Post ID" />
			<button type="button" class="button button-small button-secondary saman-seo-preview-apply-id">
				<?php esc_html_e( 'Apply', 'saman-seo' ); ?>
			</button>
			<span class="saman-seo-preview-source-status" style="font-size: 11px; color: #646970;"></span>
		</div>
	</div>

	<div class="saman-seo-google-preview__body">
		<div class="saman-seo-google-preview__url">
			<?php 
			$site_icon = get_site_icon_url( 32 );
			if ( $site_icon ) : 
			?>
				<img src="<?php echo esc_url( $site_icon ); ?>" class="saman-seo-google-preview__favicon-img" alt="Favicon" style="width: 16px; height: 16px; object-fit: contain; border-radius: 50%;" />
			<?php else : ?>
				<span class="dashicons dashicons-admin-site-alt3 saman-seo-google-preview__favicon"></span>
			<?php endif; ?>
			<?php echo esc_html( $preview_domain ); ?> â€º ...
		</div>
		<div class="saman-seo-google-preview__title" data-preview-title>
			<?php echo esc_html( $preview_title ); ?>
		</div>
		<div class="saman-seo-google-preview__description" data-preview-description>
			<?php echo esc_html( $preview_description ); ?>
		</div>
	</div>
	<div class="saman-seo-google-preview__footer">
		<span class="saman-seo-google-preview__chars">
			<span class="saman-seo-char-count" data-type="title">0</span> / 60 <?php esc_html_e( 'chars (title)', 'saman-seo' ); ?>
		</span>
		<span class="saman-seo-google-preview__chars">
			<span class="saman-seo-char-count" data-type="description">0</span> / 155 <?php esc_html_e( 'chars (description)', 'saman-seo' ); ?>
		</span>
	</div>
</div>
