<?php
/**
 * Internal Linking admin screen.
 *
 * @var array  $rules
 * @var array  $all_rules
 * @var array  $categories
 * @var array  $category_usage
 * @var array  $category_to_edit
 * @var array  $utm_templates
 * @var array  $template_to_edit
 * @var array  $settings
 * @var array  $post_types
 * @var array  $filters
 * @var string $active_tab
 * @var array  $rule_to_edit
 * @var array  $rule_defaults
 * @var array  $category_default
 * @var array  $template_default
 * @var string $page_slug
 * @var string $page_url
 * @var string $capability
 *
 * @package WPSEOPilot
 */

$tabs = [
	'rules'      => __( 'Rules', 'saman-labs-seo' ),
	'new'        => __( 'Add Rule', 'saman-labs-seo' ),
	'categories' => __( 'Categories', 'saman-labs-seo' ),
	'utms'       => __( 'UTM Templates', 'saman-labs-seo' ),
	'settings'   => __( 'Settings', 'saman-labs-seo' ),
];

$tab_url = static function ( $tab, $extra = [] ) use ( $page_url ) {
	return esc_url( add_query_arg( array_merge( [ 'tab' => $tab ], $extra ), $page_url ) );
};

$current_rule = $rule_to_edit ?: $rule_defaults;

// Render top bar
\WPSEOPilot\Admin_Topbar::render( 'internal-linking' );
?>
<div class="wrap wpseopilot-page wpseopilot-links">

	<?php settings_errors( 'samanlabs_seo_links' ); ?>

	<h2 class="nav-tab-wrapper wpseopilot-links__tabs">
		<?php foreach ( $tabs as $tab => $label ) : ?>
			<a href="<?php echo $tab_url( $tab ); ?>" class="nav-tab <?php echo ( $active_tab === $tab ) ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<div class="wpseopilot-links__panel">
		<?php if ( 'rules' === $active_tab ) : ?>
			<?php include __DIR__ . '/partials/internal-linking-rules.php'; ?>
		<?php elseif ( in_array( $active_tab, [ 'new', 'edit' ], true ) ) : ?>
			<?php include __DIR__ . '/partials/internal-linking-rule-form.php'; ?>
		<?php elseif ( 'categories' === $active_tab ) : ?>
			<?php include __DIR__ . '/partials/internal-linking-categories.php'; ?>
		<?php elseif ( 'utms' === $active_tab ) : ?>
			<?php include __DIR__ . '/partials/internal-linking-utms.php'; ?>
		<?php elseif ( 'settings' === $active_tab ) : ?>
			<?php include __DIR__ . '/partials/internal-linking-settings.php'; ?>
		<?php endif; ?>
	</div>
</div>
