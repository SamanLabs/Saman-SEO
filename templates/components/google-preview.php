<?php
/**
 * Google Search Preview Component
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

$preview_title = $preview_title ?? get_bloginfo( 'name' );
$preview_url = $preview_url ?? home_url();
$preview_description = $preview_description ?? get_bloginfo( 'description' );
$preview_domain = parse_url( home_url(), PHP_URL_HOST );
?>

<div class="samanlabs-seo-google-preview">
	<div class="samanlabs-seo-google-preview__header">
		<div style="display: flex; align-items: center; gap: 8px;">
			<span class="dashicons dashicons-search"></span>
			<span><?php esc_html_e( 'Google Search Preview', 'saman-labs-seo' ); ?></span>
		</div>
		<button type="button" class="button button-small samanlabs-seo-preview-source-toggle" style="margin-left: auto;">
			<?php esc_html_e( 'Change Source', 'saman-labs-seo' ); ?>
		</button>
	</div>
	
	<!-- Preview Source Control -->
	<div class="samanlabs-seo-preview-source-panel" style="display: none; padding: 10px 15px; background: #f0f0f1; border-bottom: 1px solid #dfe3ec;">
		<div style="display: flex; gap: 8px; align-items: center;">
			<label style="font-size: 12px; font-weight: 500;"><?php esc_html_e( 'Preview Data From:', 'saman-labs-seo' ); ?></label>
			<input type="number" class="small-text samanlabs-seo-preview-object-id-input" placeholder="Post ID" />
			<button type="button" class="button button-small button-secondary samanlabs-seo-preview-apply-id">
				<?php esc_html_e( 'Apply', 'saman-labs-seo' ); ?>
			</button>
			<span class="samanlabs-seo-preview-source-status" style="font-size: 11px; color: #646970;"></span>
		</div>
	</div>

	<div class="samanlabs-seo-google-preview__body">
		<div class="samanlabs-seo-google-preview__url">
			<?php 
			$site_icon = get_site_icon_url( 32 );
			if ( $site_icon ) : 
			?>
				<img src="<?php echo esc_url( $site_icon ); ?>" class="samanlabs-seo-google-preview__favicon-img" alt="Favicon" style="width: 16px; height: 16px; object-fit: contain; border-radius: 50%;" />
			<?php else : ?>
				<span class="dashicons dashicons-admin-site-alt3 samanlabs-seo-google-preview__favicon"></span>
			<?php endif; ?>
			<?php echo esc_html( $preview_domain ); ?> › ...
		</div>
		<div class="samanlabs-seo-google-preview__title" data-preview-title>
			<?php echo esc_html( $preview_title ); ?>
		</div>
		<div class="samanlabs-seo-google-preview__description" data-preview-description>
			<?php echo esc_html( $preview_description ); ?>
		</div>
	</div>
	<div class="samanlabs-seo-google-preview__footer">
		<span class="samanlabs-seo-google-preview__chars">
			<span class="samanlabs-seo-char-count" data-type="title">0</span> / 60 <?php esc_html_e( 'chars (title)', 'saman-labs-seo' ); ?>
		</span>
		<span class="samanlabs-seo-google-preview__chars">
			<span class="samanlabs-seo-char-count" data-type="description">0</span> / 155 <?php esc_html_e( 'chars (description)', 'saman-labs-seo' ); ?>
		</span>
	</div>
</div>
