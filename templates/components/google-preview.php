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

<div class="wpseopilot-google-preview">
	<div class="wpseopilot-google-preview__header">
		<span class="dashicons dashicons-search"></span>
		<span><?php esc_html_e( 'Google Search Preview', 'wp-seo-pilot' ); ?></span>
	</div>
	<div class="wpseopilot-google-preview__body">
		<div class="wpseopilot-google-preview__url">
			<span class="dashicons dashicons-admin-site-alt3 wpseopilot-google-preview__favicon"></span>
			<?php echo esc_html( $preview_domain ); ?> › ...
		</div>
		<div class="wpseopilot-google-preview__title" data-preview-title>
			<?php echo esc_html( $preview_title ); ?>
		</div>
		<div class="wpseopilot-google-preview__description" data-preview-description>
			<?php echo esc_html( $preview_description ); ?>
		</div>
	</div>
	<div class="wpseopilot-google-preview__footer">
		<span class="wpseopilot-google-preview__chars">
			<span class="wpseopilot-char-count" data-type="title">0</span> / 60 <?php esc_html_e( 'chars (title)', 'wp-seo-pilot' ); ?>
		</span>
		<span class="wpseopilot-google-preview__chars">
			<span class="wpseopilot-char-count" data-type="description">0</span> / 155 <?php esc_html_e( 'chars (description)', 'wp-seo-pilot' ); ?>
		</span>
	</div>
</div>
