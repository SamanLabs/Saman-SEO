<?php
/**
 * Knowledge Panel Preview Component
 *
 * Visual preview showing how business appears in Google search results.
 *
 * Expected variables from parent template (local-seo.php):
 * @var string $business_name    Business name
 * @var string $business_type    Business type (LocalBusiness, Restaurant, etc.)
 * @var string $description      Business description
 * @var string $logo             Logo URL
 * @var string $image            Cover image URL
 * @var string $phone            Phone number
 * @var string $email            Email address
 * @var string $street           Street address
 * @var string $city             City
 * @var string $state            State
 * @var string $zip              ZIP code
 * @var string $price_range      Price range ($, $$, etc.)
 * @var array  $social_profiles  Social profile URLs
 * @var array  $opening_hours    Opening hours data
 * @var string $entity_type      Entity type (organization or person)
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

// Ensure all variables have default values to prevent null errors.
$business_name   = isset( $business_name ) ? (string) $business_name : '';
$business_type   = isset( $business_type ) ? (string) $business_type : 'LocalBusiness';
$description     = isset( $description ) ? (string) $description : '';
$logo            = isset( $logo ) ? (string) $logo : '';
$image           = isset( $image ) ? (string) $image : '';
$phone           = isset( $phone ) ? (string) $phone : '';
$email           = isset( $email ) ? (string) $email : '';
$street          = isset( $street ) ? (string) $street : '';
$city            = isset( $city ) ? (string) $city : '';
$state           = isset( $state ) ? (string) $state : '';
$zip             = isset( $zip ) ? (string) $zip : '';
$price_range     = isset( $price_range ) ? (string) $price_range : '';
$social_profiles = isset( $social_profiles ) && is_array( $social_profiles ) ? $social_profiles : [];
$opening_hours   = isset( $opening_hours ) && is_array( $opening_hours ) ? $opening_hours : [];
$entity_type     = isset( $entity_type ) ? (string) $entity_type : 'organization';

// Build address string.
$address_parts = array_filter( [ $street, $city, $state ] );
$address_string = implode( ', ', $address_parts );
if ( ! empty( $zip ) ) {
	$address_string .= ' ' . $zip;
}

// Calculate if currently open.
$is_open = false;
$hours_status_text = __( 'Hours not set', 'saman-seo' );

if ( ! empty( $opening_hours ) && is_array( $opening_hours ) ) {
	$current_day = strtolower( gmdate( 'l' ) );
	$current_time = gmdate( 'H:i' );

	if ( isset( $opening_hours[ $current_day ] ) && ! empty( $opening_hours[ $current_day ]['enabled'] ) && '1' === $opening_hours[ $current_day ]['enabled'] ) {
		$open_time = $opening_hours[ $current_day ]['open'] ?? '';
		$close_time = $opening_hours[ $current_day ]['close'] ?? '';

		if ( ! empty( $open_time ) && ! empty( $close_time ) ) {
			if ( $current_time >= $open_time && $current_time <= $close_time ) {
				$is_open = true;
				$hours_status_text = sprintf(
					/* translators: %s: closing time */
					__( 'Open - Closes %s', 'saman-seo' ),
					gmdate( 'g:i A', strtotime( $close_time ) )
				);
			} else {
				$hours_status_text = sprintf(
					/* translators: %s: opening time */
					__( 'Closed - Opens %s', 'saman-seo' ),
					gmdate( 'g:i A', strtotime( $open_time ) )
				);
			}
		}
	} else {
		$hours_status_text = __( 'Closed today', 'saman-seo' );
	}
}

// Get business type label.
$business_type_label = ucwords( str_replace( [ 'LocalBusiness', '_' ], [ 'Local Business', ' ' ], $business_type ?? 'Local Business' ) );

// Detect social platforms for icons.
$social_icons = [];
if ( ! empty( $social_profiles ) && is_array( $social_profiles ) ) {
	foreach ( $social_profiles as $url ) {
		if ( empty( $url ) ) {
			continue;
		}
		if ( strpos( $url, 'facebook.com' ) !== false ) {
			$social_icons[] = [ 'url' => $url, 'icon' => 'facebook', 'dashicon' => 'dashicons-facebook' ];
		} elseif ( strpos( $url, 'twitter.com' ) !== false || strpos( $url, 'x.com' ) !== false ) {
			$social_icons[] = [ 'url' => $url, 'icon' => 'twitter', 'dashicon' => 'dashicons-twitter' ];
		} elseif ( strpos( $url, 'instagram.com' ) !== false ) {
			$social_icons[] = [ 'url' => $url, 'icon' => 'instagram', 'dashicon' => 'dashicons-instagram' ];
		} elseif ( strpos( $url, 'linkedin.com' ) !== false ) {
			$social_icons[] = [ 'url' => $url, 'icon' => 'linkedin', 'dashicon' => 'dashicons-linkedin' ];
		} elseif ( strpos( $url, 'youtube.com' ) !== false ) {
			$social_icons[] = [ 'url' => $url, 'icon' => 'youtube', 'dashicon' => 'dashicons-youtube' ];
		} else {
			$social_icons[] = [ 'url' => $url, 'icon' => 'link', 'dashicon' => 'dashicons-admin-links' ];
		}
	}
}

// Entity type icon.
$entity_icon = ( $entity_type ?? 'organization' ) === 'person' ? 'dashicons-admin-users' : 'dashicons-building';
?>

<div class="saman-seo-knowledge-panel" data-component="saman-seo-knowledge-panel">
	<div class="saman-seo-knowledge-panel__header">
		<span class="dashicons dashicons-google"></span>
		<?php esc_html_e( 'Knowledge Panel Preview', 'saman-seo' ); ?>
	</div>

	<?php if ( ! empty( $image ) ) : ?>
		<div class="saman-seo-knowledge-panel__cover has-image" style="background-image: url('<?php echo esc_url( $image ); ?>');" data-preview-cover></div>
	<?php else : ?>
		<div class="saman-seo-knowledge-panel__cover" data-preview-cover>
			<span class="dashicons dashicons-format-image"></span>
		</div>
	<?php endif; ?>

	<div class="saman-seo-knowledge-panel__body">
		<div class="saman-seo-knowledge-panel__identity">
			<?php if ( ! empty( $logo ) ) : ?>
				<div class="saman-seo-knowledge-panel__logo has-image" style="background-image: url('<?php echo esc_url( $logo ); ?>');" data-preview-logo></div>
			<?php else : ?>
				<div class="saman-seo-knowledge-panel__logo" data-preview-logo>
					<span class="dashicons <?php echo esc_attr( $entity_icon ); ?>"></span>
				</div>
			<?php endif; ?>

			<div class="saman-seo-knowledge-panel__info">
				<h3 class="saman-seo-knowledge-panel__name <?php echo empty( $business_name ) ? 'is-empty' : ''; ?>" data-preview-name>
					<?php echo ! empty( $business_name ) ? esc_html( $business_name ) : esc_html__( 'Business Name', 'saman-seo' ); ?>
				</h3>
				<p class="saman-seo-knowledge-panel__type" data-preview-type>
					<?php echo esc_html( $business_type_label ); ?>
				</p>
			</div>
		</div>

		<?php if ( ! empty( $description ) ) : ?>
			<p class="saman-seo-knowledge-panel__description" data-preview-description>
				<?php echo esc_html( $description ); ?>
			</p>
		<?php else : ?>
			<p class="saman-seo-knowledge-panel__description is-empty" data-preview-description>
				<?php esc_html_e( 'Add a description to tell customers about your business.', 'saman-seo' ); ?>
			</p>
		<?php endif; ?>

		<div class="saman-seo-knowledge-panel__details">
			<?php if ( ! empty( $address_string ) ) : ?>
				<div class="saman-seo-knowledge-panel__detail" data-preview-address>
					<span class="dashicons dashicons-location"></span>
					<span><?php echo esc_html( $address_string ); ?></span>
				</div>
			<?php else : ?>
				<div class="saman-seo-knowledge-panel__detail is-empty" data-preview-address>
					<span class="dashicons dashicons-location"></span>
					<span><?php esc_html_e( 'No address set', 'saman-seo' ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $phone ) ) : ?>
				<div class="saman-seo-knowledge-panel__detail" data-preview-phone>
					<span class="dashicons dashicons-phone"></span>
					<span><?php echo esc_html( $phone ); ?></span>
				</div>
			<?php else : ?>
				<div class="saman-seo-knowledge-panel__detail is-empty" data-preview-phone>
					<span class="dashicons dashicons-phone"></span>
					<span><?php esc_html_e( 'No phone set', 'saman-seo' ); ?></span>
				</div>
			<?php endif; ?>

			<div class="saman-seo-knowledge-panel__detail" data-preview-hours>
				<span class="dashicons dashicons-clock"></span>
				<span class="saman-seo-knowledge-panel__hours-status <?php echo $is_open ? 'is-open' : 'is-closed'; ?>" data-preview-hours-status>
					<?php echo esc_html( $hours_status_text ); ?>
				</span>
			</div>

			<?php if ( ! empty( $price_range ) ) : ?>
				<div class="saman-seo-knowledge-panel__detail" data-preview-price>
					<span class="dashicons dashicons-money-alt"></span>
					<span class="saman-seo-knowledge-panel__price"><?php echo esc_html( $price_range ); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $social_icons ) ) : ?>
			<div class="saman-seo-knowledge-panel__social" data-preview-social>
				<?php foreach ( $social_icons as $social ) : ?>
					<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener" title="<?php echo esc_attr( ucfirst( $social['icon'] ) ); ?>">
						<span class="dashicons <?php echo esc_attr( $social['dashicon'] ); ?>"></span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="saman-seo-knowledge-panel__actions">
		<a href="https://search.google.com/test/rich-results" class="button button-secondary" target="_blank" rel="noopener">
			<span class="dashicons dashicons-external"></span>
			<?php esc_html_e( 'Test with Google', 'saman-seo' ); ?>
		</a>
		<button type="button" class="button button-secondary" data-action="view-schema">
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'View JSON Schema', 'saman-seo' ); ?>
		</button>
	</div>
</div>
