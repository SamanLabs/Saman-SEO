<?php
/**
 * Search Appearance Settings Template
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

// Get all public post types
$post_types = get_post_types(
	[
		'public'             => true,
		'publicly_queryable' => true,
	],
	'objects'
);

// Get all public taxonomies
$taxonomies = get_taxonomies(
	[
		'public'             => true,
		'publicly_queryable' => true,
	],
	'objects'
);

// Variables $homepage_defaults, $post_type_defaults, $taxonomy_defaults, $archive_defaults
// are passed from the service render method

// Render top bar
\WPSEOPilot\Admin_Topbar::render( 'types' );
?>

<div class="wrap wpseopilot-page wpseopilot-search-appearance-page">
	<form method="post" action="options.php">
		<?php settings_fields( 'wpseopilot_search_appearance' ); ?>

		<div class="wpseopilot-tabs" data-component="wpseopilot-tabs">
			<div class="nav-tab-wrapper wpseopilot-tabs__nav" role="tablist">
				<a href="#global" class="nav-tab nav-tab-active" data-wpseopilot-tab="wpseopilot-tab-global">
					<?php esc_html_e( 'Global Settings', 'wp-seo-pilot' ); ?>
				</a>
				<a href="#content-types" class="nav-tab" data-wpseopilot-tab="wpseopilot-tab-content-types">
					<?php esc_html_e( 'Content Types', 'wp-seo-pilot' ); ?>
				</a>
				<a href="#taxonomies" class="nav-tab" data-wpseopilot-tab="wpseopilot-tab-taxonomies">
					<?php esc_html_e( 'Taxonomies', 'wp-seo-pilot' ); ?>
				</a>
				<a href="#archives" class="nav-tab" data-wpseopilot-tab="wpseopilot-tab-archives">
					<?php esc_html_e( 'Archives', 'wp-seo-pilot' ); ?>
				</a>
			<a href="#social" class="nav-tab" data-wpseopilot-tab="wpseopilot-tab-social">
				<?php esc_html_e( 'Social Settings', 'wp-seo-pilot' ); ?>
			</a>
			<a href="#social-cards" class="nav-tab" data-wpseopilot-tab="wpseopilot-tab-social-cards">
				<?php esc_html_e( 'Social Cards', 'wp-seo-pilot' ); ?>
			</a>
			</div>
			<!-- Global Settings Tab -->
			<div id="wpseopilot-tab-global" class="wpseopilot-tab-panel is-active">
				<div class="wpseopilot-settings-grid">
					<div class="wpseopilot-settings-main">

						<!-- Homepage Defaults Card -->
						<div class="wpseopilot-card">
							<div class="wpseopilot-card-header">
								<h2><?php esc_html_e( 'Homepage Defaults', 'wp-seo-pilot' ); ?></h2>
								<p><?php esc_html_e( 'Configure default SEO settings for your homepage.', 'wp-seo-pilot' ); ?></p>
							</div>
							<div class="wpseopilot-card-body">

								<!-- Google Preview -->
								<?php
								$preview_title = $homepage_defaults['meta_title'] ?? get_bloginfo( 'name' );
								$preview_description = $homepage_defaults['meta_description'] ?? get_bloginfo( 'description' );
								$preview_url = home_url();
								include WPSEOPILOT_PATH . 'templates/components/google-preview.php';
								?>

								<div class="wpseopilot-form-row wpseopilot-form-row--separator">
									<label for="homepage_meta_title">
										<strong><?php esc_html_e( 'Meta Title', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint"><?php esc_html_e( 'The title tag for your homepage', 'wp-seo-pilot' ); ?></span>
									</label>
									<div class="wpseopilot-flex-input">
										<input
											type="text"
											id="homepage_meta_title"
											name="wpseopilot_homepage_defaults[meta_title]"
											value="<?php echo esc_attr( $homepage_defaults['meta_title'] ?? '' ); ?>"
											class="regular-text"
											data-preview-field="title"
											data-context="global"
										/>
										<button type="button" class="button wpseopilot-trigger-vars" data-target="homepage_meta_title">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
										</button>
									</div>
								</div>

								<div class="wpseopilot-form-row">
									<label for="homepage_meta_description">
										<strong><?php esc_html_e( 'Meta Description', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint"><?php esc_html_e( 'The meta description for your homepage', 'wp-seo-pilot' ); ?></span>
									</label>
									<div class="wpseopilot-flex-input">
										<textarea
											id="homepage_meta_description"
											name="wpseopilot_homepage_defaults[meta_description]"
											rows="3"
											class="large-text"
											data-preview-field="description"
											data-context="global"
										><?php echo esc_textarea( $homepage_defaults['meta_description'] ?? '' ); ?></textarea>
										<button type="button" class="button wpseopilot-trigger-vars" data-target="homepage_meta_description">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
										</button>
									</div>
								</div>

								<div class="wpseopilot-form-row">
									<label for="homepage_meta_keywords">
										<strong><?php esc_html_e( 'Meta Keywords', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint"><?php esc_html_e( 'Comma-separated keywords (optional)', 'wp-seo-pilot' ); ?></span>
									</label>
									<input
										type="text"
										id="homepage_meta_keywords"
										name="wpseopilot_homepage_defaults[meta_keywords]"
										value="<?php echo esc_attr( $homepage_defaults['meta_keywords'] ?? '' ); ?>"
										class="regular-text"
									/>
								</div>

								<div class="wpseopilot-form-row wpseopilot-form-row--separator">
									<label>
										<strong><?php esc_html_e( 'Title Divider', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint"><?php esc_html_e( 'Choose the character that separates title parts', 'wp-seo-pilot' ); ?></span>
									</label>
									<?php
									$current_separator = get_option( 'wpseopilot_title_separator', '-' );
									$separators = [
										'-'  => [ 'label' => 'Hyphen', 'preview' => '-' ],
										'–'  => [ 'label' => 'En Dash', 'preview' => '–' ],
										'—'  => [ 'label' => 'Em Dash', 'preview' => '—' ],
										'|'  => [ 'label' => 'Pipe', 'preview' => '|' ],
										'/'  => [ 'label' => 'Slash', 'preview' => '/' ],
										'»'  => [ 'label' => 'Guillemet', 'preview' => '»' ],
										'›'  => [ 'label' => 'Angle', 'preview' => '›' ],
										'·'  => [ 'label' => 'Dot', 'preview' => '·' ],
										'•'  => [ 'label' => 'Bullet', 'preview' => '•' ],
										'→'  => [ 'label' => 'Arrow', 'preview' => '→' ],
									];
									?>
									<div class="wpseopilot-separator-selector" data-component="separator-selector">
										<div class="wpseopilot-separator-grid">
											<?php foreach ( $separators as $value => $config ) : ?>
												<button
													type="button"
													class="wpseopilot-separator-option<?php echo ( $current_separator === $value ) ? ' is-active' : ''; ?>"
													data-separator="<?php echo esc_attr( $value ); ?>"
													title="<?php echo esc_attr( $config['label'] ); ?>"
												>
													<span class="wpseopilot-separator-preview"><?php echo esc_html( $config['preview'] ); ?></span>
													<span class="wpseopilot-separator-label"><?php echo esc_html( $config['label'] ); ?></span>
												</button>
											<?php endforeach; ?>
											<button
												type="button"
												class="wpseopilot-separator-option wpseopilot-separator-custom<?php echo ( ! isset( $separators[ $current_separator ] ) && ! empty( $current_separator ) ) ? ' is-active' : ''; ?>"
												data-separator="custom"
												title="<?php esc_attr_e( 'Custom', 'wp-seo-pilot' ); ?>"
											>
												<span class="wpseopilot-separator-preview">
													<?php echo isset( $separators[ $current_separator ] ) ? '?' : esc_html( $current_separator ); ?>
												</span>
												<span class="wpseopilot-separator-label"><?php esc_html_e( 'Custom', 'wp-seo-pilot' ); ?></span>
											</button>
										</div>
										<div class="wpseopilot-separator-custom-input" style="<?php echo isset( $separators[ $current_separator ] ) ? 'display: none;' : ''; ?>">
											<input
												type="text"
												id="wpseopilot_custom_separator"
												class="small-text"
												maxlength="3"
												placeholder="<?php esc_attr_e( 'Enter custom separator', 'wp-seo-pilot' ); ?>"
												value="<?php echo isset( $separators[ $current_separator ] ) ? '' : esc_attr( $current_separator ); ?>"
											/>
										</div>
										<input
											type="hidden"
											id="wpseopilot_title_separator"
											name="wpseopilot_title_separator"
											value="<?php echo esc_attr( $current_separator ); ?>"
										/>
										<p class="description">
											<?php esc_html_e( 'This character appears in the {{separator}} variable used in title templates.', 'wp-seo-pilot' ); ?>
											<?php
											printf(
												/* translators: %s: separator character */
												esc_html__( 'Example: "My Page %s My Site"', 'wp-seo-pilot' ),
												'<code>' . esc_html( $current_separator ) . '</code>'
											);
											?>
										</p>
									</div>
								</div>

							</div>
						</div>

					</div>

					<div class="wpseopilot-settings-sidebar">
						<div class="wpseopilot-info-card">
							<h3><?php esc_html_e( 'Homepage SEO', 'wp-seo-pilot' ); ?></h3>
							<p><?php esc_html_e( 'Your homepage is often the most important page for SEO. Make sure to craft compelling title and description tags.', 'wp-seo-pilot' ); ?></p>
							<ul class="wpseopilot-info-list">
								<li><?php esc_html_e( 'Keep title under 60 characters', 'wp-seo-pilot' ); ?></li>
								<li><?php esc_html_e( 'Keep description under 155 characters', 'wp-seo-pilot' ); ?></li>
								<li><?php esc_html_e( 'Include your primary keywords', 'wp-seo-pilot' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<!-- Content Types Tab -->
			<div id="wpseopilot-tab-content-types" class="wpseopilot-tab-panel">
				<div class="wpseopilot-card wpseopilot-card-body--no-padding">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Content Type Settings', 'wp-seo-pilot' ); ?></h2>
						<p><?php esc_html_e( 'Configure SEO settings for each post type.', 'wp-seo-pilot' ); ?></p>
					</div>

					<?php foreach ( $post_types as $slug => $object ) : ?>
						<?php
						$label = $object->labels->name ?? $slug;
						$settings = $post_type_defaults[ $slug ] ?? [];
						?>
						<details class="wpseopilot-accordion" data-accordion-slug="<?php echo esc_attr( $slug ); ?>">
							<summary>
								<span class="wpseopilot-accordion-title"><?php echo esc_html( $label ); ?></span>
								<span class="wpseopilot-accordion-badge"><?php echo esc_html( $slug ); ?></span>
							</summary>
							<div class="wpseopilot-accordion__body">

								<!-- Google Preview (shared across all sub-tabs) -->
								<div class="wpseopilot-accordion__preview">
									<?php
									$preview_title = $settings['title_template'] ?? '{{post_title}} {{separator}} {{site_title}}';
									$preview_description = $settings['description_template'] ?? '{{post_excerpt}}';
									$preview_url = home_url();
									include WPSEOPILOT_PATH . 'templates/components/google-preview.php';
									?>
								</div>

								<!-- Nested Sub-Tabs -->
								<div class="wpseopilot-accordion-tabs" data-component="wpseopilot-accordion-tabs">
									<div class="wpseopilot-accordion-tabs__nav" role="tablist">
										<button
											type="button"
											class="wpseopilot-accordion-tab is-active"
											data-accordion-tab="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-title-description"
											aria-selected="true"
											role="tab"
										>
											<?php esc_html_e( 'Title & Description', 'wp-seo-pilot' ); ?>
										</button>
										<button
											type="button"
											class="wpseopilot-accordion-tab"
											data-accordion-tab="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-schema"
											aria-selected="false"
											role="tab"
										>
											<?php esc_html_e( 'Schema Markup', 'wp-seo-pilot' ); ?>
										</button>
										<button
											type="button"
											class="wpseopilot-accordion-tab"
											data-accordion-tab="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-custom-fields"
											aria-selected="false"
											role="tab"
										>
											<?php esc_html_e( 'Custom Fields', 'wp-seo-pilot' ); ?>
										</button>
										<button
											type="button"
											class="wpseopilot-accordion-tab"
											data-accordion-tab="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-advanced"
											aria-selected="false"
											role="tab"
										>
											<?php esc_html_e( 'Advanced Settings', 'wp-seo-pilot' ); ?>
										</button>
									</div>

									<!-- Sub-Tab Panels -->
									<div
										id="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-title-description"
										class="wpseopilot-accordion-tab-panel is-active"
										role="tabpanel"
									>
										<?php include WPSEOPILOT_PATH . 'templates/components/post-type-fields/title-description.php'; ?>
									</div>

									<div
										id="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-schema"
										class="wpseopilot-accordion-tab-panel"
										role="tabpanel"
									>
										<?php include WPSEOPILOT_PATH . 'templates/components/post-type-fields/schema.php'; ?>
									</div>

									<div
										id="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-custom-fields"
										class="wpseopilot-accordion-tab-panel"
										role="tabpanel"
									>
										<?php include WPSEOPILOT_PATH . 'templates/components/post-type-fields/custom-fields.php'; ?>
									</div>

									<div
										id="wpseopilot-accordion-<?php echo esc_attr( $slug ); ?>-advanced"
										class="wpseopilot-accordion-tab-panel"
										role="tabpanel"
									>
										<?php include WPSEOPILOT_PATH . 'templates/components/post-type-fields/advanced.php'; ?>
									</div>
								</div>

							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Taxonomies Tab -->
			<div id="wpseopilot-tab-taxonomies" class="wpseopilot-tab-panel">
				<div class="wpseopilot-card wpseopilot-card-body--no-padding">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Taxonomy Settings', 'wp-seo-pilot' ); ?></h2>
						<p><?php esc_html_e( 'Configure SEO settings for category and tag archives.', 'wp-seo-pilot' ); ?></p>
					</div>

					<?php foreach ( $taxonomies as $slug => $object ) : ?>
						<?php
						$label = $object->labels->name ?? $slug;
						$settings = $taxonomy_defaults[ $slug ] ?? [];
						?>
						<details class="wpseopilot-accordion">
							<summary>
								<span class="wpseopilot-accordion-title"><?php echo esc_html( $label ); ?></span>
								<span class="wpseopilot-accordion-badge"><?php echo esc_html( $slug ); ?></span>
							</summary>
							<div class="wpseopilot-accordion__body">

								<!-- Google Preview -->
								<?php
								// Use settings values with defaults
								$preview_title = ! empty( $settings['title_template'] ) ? $settings['title_template'] : '{{term}} Archives {{separator}} {{sitename}}';
								$preview_description = ! empty( $settings['description_template'] ) ? $settings['description_template'] : '{{term_description}}';
								$preview_url = home_url();
								include WPSEOPILOT_PATH . 'templates/components/google-preview.php';
								?>

								<div class="wpseopilot-form-row wpseopilot-form-row--separator">
									<label>
										<strong><?php esc_html_e( 'Show in Search Results?', 'wp-seo-pilot' ); ?></strong>
									</label>
									<label class="wpseopilot-toggle">
										<input
											type="checkbox"
											name="wpseopilot_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][noindex]"
											value="1"
											<?php checked( $settings['noindex'] ?? false, 1 ); ?>
										/>
										<span class="wpseopilot-toggle-label">
											<?php esc_html_e( 'Hide from search engines (noindex)', 'wp-seo-pilot' ); ?>
										</span>
									</label>
								</div>

								<div class="wpseopilot-form-row">
									<label>
										<strong><?php esc_html_e( 'Title Template', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint">
											<?php esc_html_e( 'Use {{term}} for term name, {{sitename}} for site name', 'wp-seo-pilot' ); ?>
										</span>
									</label>
									<div class="wpseopilot-flex-input">
										<input
											type="text"
											name="wpseopilot_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][title_template]"
											value="<?php echo esc_attr( $settings['title_template'] ?? '{{term}} Archives {{separator}} {{sitename}}' ); ?>"
											class="regular-text"
											data-preview-field="title"
											data-context="taxonomy:<?php echo esc_attr( $slug ); ?>"
											placeholder="<?php echo esc_attr__( '{{term}} Archives {{separator}} {{sitename}}', 'wp-seo-pilot' ); ?>"
										/>
										<button type="button" class="button wpseopilot-trigger-vars" data-target="wpseopilot_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][title_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
										</button>
									</div>
								</div>

								<div class="wpseopilot-form-row">
									<label>
										<strong><?php esc_html_e( 'Description Template', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint">
											<?php esc_html_e( 'Use {{term_description}} for term description', 'wp-seo-pilot' ); ?>
										</span>
									</label>
									<div class="wpseopilot-flex-input">
										<textarea
											name="wpseopilot_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][description_template]"
											rows="2"
											class="large-text"
											data-preview-field="description"
											data-context="taxonomy:<?php echo esc_attr( $slug ); ?>"
											placeholder="<?php echo esc_attr__( '{{term_description}}', 'wp-seo-pilot' ); ?>"
										><?php echo esc_textarea( $settings['description_template'] ?? '{{term_description}}' ); ?></textarea>
										<button type="button" class="button wpseopilot-trigger-vars" data-target="wpseopilot_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][description_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
										</button>
									</div>
								</div>

							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Archives Tab -->
			<div id="wpseopilot-tab-archives" class="wpseopilot-tab-panel">
				<div class="wpseopilot-card wpseopilot-card-body--no-padding">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Archive & Special Page Settings', 'wp-seo-pilot' ); ?></h2>
						<p><?php esc_html_e( 'Configure SEO settings for author, date, search archives, and special pages like 404.', 'wp-seo-pilot' ); ?></p>
					</div>

					<?php
					$archive_types = [
						'author' => __( 'Author Archives', 'wp-seo-pilot' ),
						'date'   => __( 'Date Archives', 'wp-seo-pilot' ),
						'search' => __( 'Search Results', 'wp-seo-pilot' ),
						'404'    => __( '404 Page', 'wp-seo-pilot' ),
					];
					?>

					<?php foreach ( $archive_types as $type => $label ) : ?>
						<?php $settings = $archive_defaults[ $type ] ?? []; ?>
						<details class="wpseopilot-accordion">
							<summary>
								<span class="wpseopilot-accordion-title"><?php echo esc_html( $label ); ?></span>
								<span class="wpseopilot-accordion-badge"><?php echo esc_html( $type ); ?></span>
							</summary>
							<div class="wpseopilot-accordion__body">

								<!-- Google Preview -->
								<?php
								// Use the settings values (which now include defaults)
								$preview_title = $settings['title_template'] ?? '';
								$preview_description = $settings['description_template'] ?? '';
								$preview_url = home_url();
								include WPSEOPILOT_PATH . 'templates/components/google-preview.php';
								?>

								<div class="wpseopilot-form-row wpseopilot-form-row--separator">
									<label>
										<strong><?php esc_html_e( 'Show in Search Results?', 'wp-seo-pilot' ); ?></strong>
									</label>
									<label class="wpseopilot-toggle">
										<input
											type="checkbox"
											name="wpseopilot_archive_defaults[<?php echo esc_attr( $type ); ?>][noindex]"
											value="1"
											<?php checked( $settings['noindex'] ?? false, 1 ); ?>
										/>
										<span class="wpseopilot-toggle-label">
											<?php esc_html_e( 'Hide from search engines (noindex)', 'wp-seo-pilot' ); ?>
										</span>
									</label>
								</div>

								<div class="wpseopilot-form-row">
									<label>
										<strong><?php esc_html_e( 'Title Template', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint">
											<?php
											if ( 'author' === $type ) {
												esc_html_e( 'Use {{author}} for author name', 'wp-seo-pilot' );
											} elseif ( 'date' === $type ) {
												esc_html_e( 'Use {{date}} for date', 'wp-seo-pilot' );
											} elseif ( 'search' === $type ) {
												esc_html_e( 'Use {{search_term}} for search query', 'wp-seo-pilot' );
											} elseif ( '404' === $type ) {
												esc_html_e( 'Use {{request_url}} for requested URL, {{sitename}} for site name', 'wp-seo-pilot' );
											}
											?>
										</span>
									</label>
									<div class="wpseopilot-flex-input">
										<input
											type="text"
											name="wpseopilot_archive_defaults[<?php echo esc_attr( $type ); ?>][title_template]"
											value="<?php echo esc_attr( $settings['title_template'] ?? '' ); ?>"
											class="regular-text"
											data-preview-field="title"
											data-context="archive:<?php echo esc_attr( $type ); ?>"
											placeholder="<?php
											if ( 'author' === $type ) {
												echo esc_attr__( '{{author}} {{separator}} {{sitename}}', 'wp-seo-pilot' );
											} elseif ( 'date' === $type ) {
												echo esc_attr__( '{{date}} Archives {{separator}} {{sitename}}', 'wp-seo-pilot' );
											} elseif ( 'search' === $type ) {
												echo esc_attr__( 'Search: {{search_term}} {{separator}} {{sitename}}', 'wp-seo-pilot' );
											} elseif ( '404' === $type ) {
												echo esc_attr__( 'Page Not Found {{separator}} {{sitename}}', 'wp-seo-pilot' );
											}
											?>"
										/>
										<button type="button" class="button wpseopilot-trigger-vars" data-target="wpseopilot_archive_defaults[<?php echo esc_attr( $type ); ?>][title_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
										</button>
									</div>
								</div>

								<div class="wpseopilot-form-row">
									<label>
										<strong><?php esc_html_e( 'Meta Description', 'wp-seo-pilot' ); ?></strong>
										<span class="wpseopilot-label-hint">
											<?php
											if ( 'author' === $type ) {
												esc_html_e( 'Description for author archive pages', 'wp-seo-pilot' );
											} elseif ( 'date' === $type ) {
												esc_html_e( 'Description for date archive pages', 'wp-seo-pilot' );
											} elseif ( 'search' === $type ) {
												esc_html_e( 'Description for search results pages', 'wp-seo-pilot' );
											} elseif ( '404' === $type ) {
												esc_html_e( 'Description shown in search results for 404 pages', 'wp-seo-pilot' );
											}
											?>
										</span>
									</label>
									<div class="wpseopilot-flex-input">
										<textarea
											name="wpseopilot_archive_defaults[<?php echo esc_attr( $type ); ?>][description_template]"
											rows="3"
											class="large-text"
											data-preview-field="description"
											data-context="archive:<?php echo esc_attr( $type ); ?>"
											placeholder="<?php
											if ( 'author' === $type ) {
												echo esc_attr__( 'Articles written by {{author}}. {{author_bio}}', 'wp-seo-pilot' );
											} elseif ( 'date' === $type ) {
												echo esc_attr__( 'Browse our articles from {{date}}.', 'wp-seo-pilot' );
											} elseif ( 'search' === $type ) {
												echo esc_attr__( 'Search results for "{{search_term}}" on {{sitename}}.', 'wp-seo-pilot' );
											} elseif ( '404' === $type ) {
												echo esc_attr__( 'The page you are looking for could not be found.', 'wp-seo-pilot' );
											}
											?>"
										><?php echo esc_textarea( $settings['description_template'] ?? '' ); ?></textarea>
										<button type="button" class="button wpseopilot-trigger-vars" data-target="wpseopilot_archive_defaults[<?php echo esc_attr( $type ); ?>][description_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
										</button>
									</div>
								</div>

							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>

		<!-- Social Settings Tab -->
		<div id="wpseopilot-tab-social" class="wpseopilot-tab-panel">
			<?php include __DIR__ . '/components/social-settings-tab.php'; ?>
		</div>

	<!-- Social Cards Tab -->
	<div id="wpseopilot-tab-social-cards" class="wpseopilot-tab-panel">
		<?php include __DIR__ . '/components/social-cards-tab.php'; ?>
	</div>

			<div class="wpseopilot-tabs__actions">
				<?php submit_button( __( 'Save Search Appearance Settings', 'wp-seo-pilot' ), 'primary', 'submit', false ); ?>
			</div>
		</div>
	</form>
</div>
