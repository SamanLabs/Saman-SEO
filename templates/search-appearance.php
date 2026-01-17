<?php
/**
 * Search Appearance Settings Template
 *
 * @package Saman\SEO
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
\Saman\SEO\Admin_Topbar::render( 'types' );
?>

<div class="wrap saman-seo-page saman-seo-search-appearance-page">
	<form method="post" action="options.php">
		<?php settings_fields( 'SAMAN_SEO_search_appearance' ); ?>

		<div class="saman-seo-tabs" data-component="saman-seo-tabs">
			<div class="nav-tab-wrapper saman-seo-tabs__nav" role="tablist">
				<a href="#global" class="nav-tab nav-tab-active" data-saman-seo-tab="saman-seo-tab-global">
					<?php esc_html_e( 'Global Settings', 'saman-seo' ); ?>
				</a>
				<a href="#content-types" class="nav-tab" data-saman-seo-tab="saman-seo-tab-content-types">
					<?php esc_html_e( 'Content Types', 'saman-seo' ); ?>
				</a>
				<a href="#taxonomies" class="nav-tab" data-saman-seo-tab="saman-seo-tab-taxonomies">
					<?php esc_html_e( 'Taxonomies', 'saman-seo' ); ?>
				</a>
				<a href="#archives" class="nav-tab" data-saman-seo-tab="saman-seo-tab-archives">
					<?php esc_html_e( 'Archives', 'saman-seo' ); ?>
				</a>
			<a href="#social" class="nav-tab" data-saman-seo-tab="saman-seo-tab-social">
				<?php esc_html_e( 'Social Settings', 'saman-seo' ); ?>
			</a>
			<a href="#social-cards" class="nav-tab" data-saman-seo-tab="saman-seo-tab-social-cards">
				<?php esc_html_e( 'Social Cards', 'saman-seo' ); ?>
			</a>
			</div>
			<!-- Global Settings Tab -->
			<div id="saman-seo-tab-global" class="saman-seo-tab-panel is-active">
				<div class="saman-seo-settings-grid">
					<div class="saman-seo-settings-main">

						<!-- Homepage Defaults Card -->
						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Homepage Defaults', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'Configure default SEO settings for your homepage.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<!-- Google Preview -->
								<?php
								$preview_title = $homepage_defaults['meta_title'] ?? get_bloginfo( 'name' );
								$preview_description = $homepage_defaults['meta_description'] ?? get_bloginfo( 'description' );
								$preview_url = home_url();
								include SAMAN_SEO_PATH . 'templates/components/google-preview.php';
								?>

								<div class="saman-seo-form-row saman-seo-form-row--separator">
									<label for="homepage_meta_title">
										<strong><?php esc_html_e( 'Meta Title', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'The title tag for your homepage', 'saman-seo' ); ?></span>
									</label>
									<div class="saman-seo-flex-input">
										<input
											type="text"
											id="homepage_meta_title"
											name="SAMAN_SEO_homepage_defaults[meta_title]"
											value="<?php echo esc_attr( $homepage_defaults['meta_title'] ?? '' ); ?>"
											class="regular-text"
											data-preview-field="title"
											data-context="global"
										/>
										<button type="button" class="button saman-seo-trigger-vars" data-target="homepage_meta_title">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
										</button>
									</div>
								</div>

								<div class="saman-seo-form-row">
									<label for="homepage_meta_description">
										<strong><?php esc_html_e( 'Meta Description', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'The meta description for your homepage', 'saman-seo' ); ?></span>
									</label>
									<div class="saman-seo-flex-input">
										<textarea
											id="homepage_meta_description"
											name="SAMAN_SEO_homepage_defaults[meta_description]"
											rows="3"
											class="large-text"
											data-preview-field="description"
											data-context="global"
										><?php echo esc_textarea( $homepage_defaults['meta_description'] ?? '' ); ?></textarea>
										<button type="button" class="button saman-seo-trigger-vars" data-target="homepage_meta_description">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
										</button>
									</div>
								</div>

								<div class="saman-seo-form-row">
									<label for="homepage_meta_keywords">
										<strong><?php esc_html_e( 'Meta Keywords', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Comma-separated keywords (optional)', 'saman-seo' ); ?></span>
									</label>
									<input
										type="text"
										id="homepage_meta_keywords"
										name="SAMAN_SEO_homepage_defaults[meta_keywords]"
										value="<?php echo esc_attr( $homepage_defaults['meta_keywords'] ?? '' ); ?>"
										class="regular-text"
									/>
								</div>

								<div class="saman-seo-form-row saman-seo-form-row--separator">
									<label>
										<strong><?php esc_html_e( 'Title Divider', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Choose the character that separates title parts', 'saman-seo' ); ?></span>
									</label>
									<?php
									$current_separator = get_option( 'SAMAN_SEO_title_separator', '-' );
									$separators = [
										'-'  => [ 'label' => 'Hyphen', 'preview' => '-' ],
										'Ã¢â‚¬â€œ'  => [ 'label' => 'En Dash', 'preview' => 'Ã¢â‚¬â€œ' ],
										'Ã¢â‚¬â€'  => [ 'label' => 'Em Dash', 'preview' => 'Ã¢â‚¬â€' ],
										'|'  => [ 'label' => 'Pipe', 'preview' => '|' ],
										'/'  => [ 'label' => 'Slash', 'preview' => '/' ],
										'Ã‚Â»'  => [ 'label' => 'Guillemet', 'preview' => 'Ã‚Â»' ],
										'Ã¢â‚¬Âº'  => [ 'label' => 'Angle', 'preview' => 'Ã¢â‚¬Âº' ],
										'Ã‚Â·'  => [ 'label' => 'Dot', 'preview' => 'Ã‚Â·' ],
										'Ã¢â‚¬Â¢'  => [ 'label' => 'Bullet', 'preview' => 'Ã¢â‚¬Â¢' ],
										'Ã¢â€ â€™'  => [ 'label' => 'Arrow', 'preview' => 'Ã¢â€ â€™' ],
									];
									?>
									<div class="saman-seo-separator-selector" data-component="separator-selector">
										<div class="saman-seo-separator-grid">
											<?php foreach ( $separators as $value => $config ) : ?>
												<button
													type="button"
													class="saman-seo-separator-option<?php echo ( $current_separator === $value ) ? ' is-active' : ''; ?>"
													data-separator="<?php echo esc_attr( $value ); ?>"
													title="<?php echo esc_attr( $config['label'] ); ?>"
												>
													<span class="saman-seo-separator-preview"><?php echo esc_html( $config['preview'] ); ?></span>
													<span class="saman-seo-separator-label"><?php echo esc_html( $config['label'] ); ?></span>
												</button>
											<?php endforeach; ?>
											<button
												type="button"
												class="saman-seo-separator-option saman-seo-separator-custom<?php echo ( ! isset( $separators[ $current_separator ] ) && ! empty( $current_separator ) ) ? ' is-active' : ''; ?>"
												data-separator="custom"
												title="<?php esc_attr_e( 'Custom', 'saman-seo' ); ?>"
											>
												<span class="saman-seo-separator-preview">
													<?php echo isset( $separators[ $current_separator ] ) ? '?' : esc_html( $current_separator ); ?>
												</span>
												<span class="saman-seo-separator-label"><?php esc_html_e( 'Custom', 'saman-seo' ); ?></span>
											</button>
										</div>
										<div class="saman-seo-separator-custom-input" style="<?php echo isset( $separators[ $current_separator ] ) ? 'display: none;' : ''; ?>">
											<input
												type="text"
												id="SAMAN_SEO_custom_separator"
												class="small-text"
												maxlength="3"
												placeholder="<?php esc_attr_e( 'Enter custom separator', 'saman-seo' ); ?>"
												value="<?php echo isset( $separators[ $current_separator ] ) ? '' : esc_attr( $current_separator ); ?>"
											/>
										</div>
										<input
											type="hidden"
											id="SAMAN_SEO_title_separator"
											name="SAMAN_SEO_title_separator"
											value="<?php echo esc_attr( $current_separator ); ?>"
										/>
										<p class="description">
											<?php esc_html_e( 'This character appears in the {{separator}} variable used in title templates.', 'saman-seo' ); ?>
											<?php
											printf(
												/* translators: %s: separator character */
												esc_html__( 'Example: "My Page %s My Site"', 'saman-seo' ),
												'<code>' . esc_html( $current_separator ) . '</code>'
											);
											?>
										</p>
									</div>
								</div>

							</div>
						</div>

					</div>

					<div class="saman-seo-settings-sidebar">
						<div class="saman-seo-info-card">
							<h3><?php esc_html_e( 'Homepage SEO', 'saman-seo' ); ?></h3>
							<p><?php esc_html_e( 'Your homepage is often the most important page for SEO. Make sure to craft compelling title and description tags.', 'saman-seo' ); ?></p>
							<ul class="saman-seo-info-list">
								<li><?php esc_html_e( 'Keep title under 60 characters', 'saman-seo' ); ?></li>
								<li><?php esc_html_e( 'Keep description under 155 characters', 'saman-seo' ); ?></li>
								<li><?php esc_html_e( 'Include your primary keywords', 'saman-seo' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<!-- Content Types Tab -->
			<div id="saman-seo-tab-content-types" class="saman-seo-tab-panel">
				<div class="saman-seo-card saman-seo-card-body--no-padding">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Content Type Settings', 'saman-seo' ); ?></h2>
						<p><?php esc_html_e( 'Configure SEO settings for each post type.', 'saman-seo' ); ?></p>
					</div>

					<?php foreach ( $post_types as $slug => $object ) : ?>
						<?php
						$label = $object->labels->name ?? $slug;
						$settings = $post_type_defaults[ $slug ] ?? [];
						?>
						<details class="saman-seo-accordion" data-accordion-slug="<?php echo esc_attr( $slug ); ?>">
							<summary>
								<span class="saman-seo-accordion-title"><?php echo esc_html( $label ); ?></span>
								<span class="saman-seo-accordion-badge"><?php echo esc_html( $slug ); ?></span>
							</summary>
							<div class="saman-seo-accordion__body">

								<!-- Google Preview (shared across all sub-tabs) -->
								<div class="saman-seo-accordion__preview">
									<?php
									$preview_title = $settings['title_template'] ?? '{{post_title}} {{separator}} {{site_title}}';
									$preview_description = $settings['description_template'] ?? '{{post_excerpt}}';
									$preview_url = home_url();
									include SAMAN_SEO_PATH . 'templates/components/google-preview.php';
									?>
								</div>

								<!-- Nested Sub-Tabs -->
								<div class="saman-seo-accordion-tabs" data-component="saman-seo-accordion-tabs">
									<div class="saman-seo-accordion-tabs__nav" role="tablist">
										<button
											type="button"
											class="saman-seo-accordion-tab is-active"
											data-accordion-tab="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-title-description"
											aria-selected="true"
											role="tab"
										>
											<?php esc_html_e( 'Title & Description', 'saman-seo' ); ?>
										</button>
										<button
											type="button"
											class="saman-seo-accordion-tab"
											data-accordion-tab="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-schema"
											aria-selected="false"
											role="tab"
										>
											<?php esc_html_e( 'Schema Markup', 'saman-seo' ); ?>
										</button>
										<button
											type="button"
											class="saman-seo-accordion-tab"
											data-accordion-tab="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-custom-fields"
											aria-selected="false"
											role="tab"
										>
											<?php esc_html_e( 'Custom Fields', 'saman-seo' ); ?>
										</button>
										<button
											type="button"
											class="saman-seo-accordion-tab"
											data-accordion-tab="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-advanced"
											aria-selected="false"
											role="tab"
										>
											<?php esc_html_e( 'Advanced Settings', 'saman-seo' ); ?>
										</button>
									</div>

									<!-- Sub-Tab Panels -->
									<div
										id="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-title-description"
										class="saman-seo-accordion-tab-panel is-active"
										role="tabpanel"
									>
										<?php include SAMAN_SEO_PATH . 'templates/components/post-type-fields/title-description.php'; ?>
									</div>

									<div
										id="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-schema"
										class="saman-seo-accordion-tab-panel"
										role="tabpanel"
									>
										<?php include SAMAN_SEO_PATH . 'templates/components/post-type-fields/schema.php'; ?>
									</div>

									<div
										id="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-custom-fields"
										class="saman-seo-accordion-tab-panel"
										role="tabpanel"
									>
										<?php include SAMAN_SEO_PATH . 'templates/components/post-type-fields/custom-fields.php'; ?>
									</div>

									<div
										id="saman-seo-accordion-<?php echo esc_attr( $slug ); ?>-advanced"
										class="saman-seo-accordion-tab-panel"
										role="tabpanel"
									>
										<?php include SAMAN_SEO_PATH . 'templates/components/post-type-fields/advanced.php'; ?>
									</div>
								</div>

							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Taxonomies Tab -->
			<div id="saman-seo-tab-taxonomies" class="saman-seo-tab-panel">
				<div class="saman-seo-card saman-seo-card-body--no-padding">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Taxonomy Settings', 'saman-seo' ); ?></h2>
						<p><?php esc_html_e( 'Configure SEO settings for category and tag archives.', 'saman-seo' ); ?></p>
					</div>

					<?php foreach ( $taxonomies as $slug => $object ) : ?>
						<?php
						$label = $object->labels->name ?? $slug;
						$settings = $taxonomy_defaults[ $slug ] ?? [];
						?>
						<details class="saman-seo-accordion">
							<summary>
								<span class="saman-seo-accordion-title"><?php echo esc_html( $label ); ?></span>
								<span class="saman-seo-accordion-badge"><?php echo esc_html( $slug ); ?></span>
							</summary>
							<div class="saman-seo-accordion__body">

								<!-- Google Preview -->
								<?php
								// Use settings values with defaults
								$preview_title = ! empty( $settings['title_template'] ) ? $settings['title_template'] : '{{term}} Archives {{separator}} {{sitename}}';
								$preview_description = ! empty( $settings['description_template'] ) ? $settings['description_template'] : '{{term_description}}';
								$preview_url = home_url();
								include SAMAN_SEO_PATH . 'templates/components/google-preview.php';
								?>

								<div class="saman-seo-form-row saman-seo-form-row--separator">
									<label>
										<strong><?php esc_html_e( 'Show in Search Results?', 'saman-seo' ); ?></strong>
									</label>
									<label class="saman-seo-toggle">
										<input
											type="checkbox"
											name="SAMAN_SEO_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][noindex]"
											value="1"
											<?php checked( $settings['noindex'] ?? false, 1 ); ?>
										/>
										<span class="saman-seo-toggle-label">
											<?php esc_html_e( 'Hide from search engines (noindex)', 'saman-seo' ); ?>
										</span>
									</label>
								</div>

								<div class="saman-seo-form-row">
									<label>
										<strong><?php esc_html_e( 'Title Template', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint">
											<?php esc_html_e( 'Use {{term}} for term name, {{sitename}} for site name', 'saman-seo' ); ?>
										</span>
									</label>
									<div class="saman-seo-flex-input">
										<input
											type="text"
											name="SAMAN_SEO_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][title_template]"
											value="<?php echo esc_attr( $settings['title_template'] ?? '{{term}} Archives {{separator}} {{sitename}}' ); ?>"
											class="regular-text"
											data-preview-field="title"
											data-context="taxonomy:<?php echo esc_attr( $slug ); ?>"
											placeholder="<?php echo esc_attr__( '{{term}} Archives {{separator}} {{sitename}}', 'saman-seo' ); ?>"
										/>
										<button type="button" class="button saman-seo-trigger-vars" data-target="SAMAN_SEO_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][title_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
										</button>
									</div>
								</div>

								<div class="saman-seo-form-row">
									<label>
										<strong><?php esc_html_e( 'Description Template', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint">
											<?php esc_html_e( 'Use {{term_description}} for term description', 'saman-seo' ); ?>
										</span>
									</label>
									<div class="saman-seo-flex-input">
										<textarea
											name="SAMAN_SEO_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][description_template]"
											rows="2"
											class="large-text"
											data-preview-field="description"
											data-context="taxonomy:<?php echo esc_attr( $slug ); ?>"
											placeholder="<?php echo esc_attr__( '{{term_description}}', 'saman-seo' ); ?>"
										><?php echo esc_textarea( $settings['description_template'] ?? '{{term_description}}' ); ?></textarea>
										<button type="button" class="button saman-seo-trigger-vars" data-target="SAMAN_SEO_taxonomy_defaults[<?php echo esc_attr( $slug ); ?>][description_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
										</button>
									</div>
								</div>

							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Archives Tab -->
			<div id="saman-seo-tab-archives" class="saman-seo-tab-panel">
				<div class="saman-seo-card saman-seo-card-body--no-padding">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Archive & Special Page Settings', 'saman-seo' ); ?></h2>
						<p><?php esc_html_e( 'Configure SEO settings for author, date, search archives, and special pages like 404.', 'saman-seo' ); ?></p>
					</div>

					<?php
					$archive_types = [
						'author' => __( 'Author Archives', 'saman-seo' ),
						'date'   => __( 'Date Archives', 'saman-seo' ),
						'search' => __( 'Search Results', 'saman-seo' ),
						'404'    => __( '404 Page', 'saman-seo' ),
					];
					?>

					<?php foreach ( $archive_types as $type => $label ) : ?>
						<?php $settings = $archive_defaults[ $type ] ?? []; ?>
						<details class="saman-seo-accordion">
							<summary>
								<span class="saman-seo-accordion-title"><?php echo esc_html( $label ); ?></span>
								<span class="saman-seo-accordion-badge"><?php echo esc_html( $type ); ?></span>
							</summary>
							<div class="saman-seo-accordion__body">

								<!-- Google Preview -->
								<?php
								// Use the settings values (which now include defaults)
								$preview_title = $settings['title_template'] ?? '';
								$preview_description = $settings['description_template'] ?? '';
								$preview_url = home_url();
								include SAMAN_SEO_PATH . 'templates/components/google-preview.php';
								?>

								<div class="saman-seo-form-row saman-seo-form-row--separator">
									<label>
										<strong><?php esc_html_e( 'Show in Search Results?', 'saman-seo' ); ?></strong>
									</label>
									<label class="saman-seo-toggle">
										<input
											type="checkbox"
											name="SAMAN_SEO_archive_defaults[<?php echo esc_attr( $type ); ?>][noindex]"
											value="1"
											<?php checked( $settings['noindex'] ?? false, 1 ); ?>
										/>
										<span class="saman-seo-toggle-label">
											<?php esc_html_e( 'Hide from search engines (noindex)', 'saman-seo' ); ?>
										</span>
									</label>
								</div>

								<div class="saman-seo-form-row">
									<label>
										<strong><?php esc_html_e( 'Title Template', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint">
											<?php
											if ( 'author' === $type ) {
												esc_html_e( 'Use {{author}} for author name', 'saman-seo' );
											} elseif ( 'date' === $type ) {
												esc_html_e( 'Use {{date}} for date', 'saman-seo' );
											} elseif ( 'search' === $type ) {
												esc_html_e( 'Use {{search_term}} for search query', 'saman-seo' );
											} elseif ( '404' === $type ) {
												esc_html_e( 'Use {{request_url}} for requested URL, {{sitename}} for site name', 'saman-seo' );
											}
											?>
										</span>
									</label>
									<div class="saman-seo-flex-input">
										<input
											type="text"
											name="SAMAN_SEO_archive_defaults[<?php echo esc_attr( $type ); ?>][title_template]"
											value="<?php echo esc_attr( $settings['title_template'] ?? '' ); ?>"
											class="regular-text"
											data-preview-field="title"
											data-context="archive:<?php echo esc_attr( $type ); ?>"
											placeholder="<?php
											if ( 'author' === $type ) {
												echo esc_attr__( '{{author}} {{separator}} {{sitename}}', 'saman-seo' );
											} elseif ( 'date' === $type ) {
												echo esc_attr__( '{{date}} Archives {{separator}} {{sitename}}', 'saman-seo' );
											} elseif ( 'search' === $type ) {
												echo esc_attr__( 'Search: {{search_term}} {{separator}} {{sitename}}', 'saman-seo' );
											} elseif ( '404' === $type ) {
												echo esc_attr__( 'Page Not Found {{separator}} {{sitename}}', 'saman-seo' );
											}
											?>"
										/>
										<button type="button" class="button saman-seo-trigger-vars" data-target="SAMAN_SEO_archive_defaults[<?php echo esc_attr( $type ); ?>][title_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
										</button>
									</div>
								</div>

								<div class="saman-seo-form-row">
									<label>
										<strong><?php esc_html_e( 'Meta Description', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint">
											<?php
											if ( 'author' === $type ) {
												esc_html_e( 'Description for author archive pages', 'saman-seo' );
											} elseif ( 'date' === $type ) {
												esc_html_e( 'Description for date archive pages', 'saman-seo' );
											} elseif ( 'search' === $type ) {
												esc_html_e( 'Description for search results pages', 'saman-seo' );
											} elseif ( '404' === $type ) {
												esc_html_e( 'Description shown in search results for 404 pages', 'saman-seo' );
											}
											?>
										</span>
									</label>
									<div class="saman-seo-flex-input">
										<textarea
											name="SAMAN_SEO_archive_defaults[<?php echo esc_attr( $type ); ?>][description_template]"
											rows="3"
											class="large-text"
											data-preview-field="description"
											data-context="archive:<?php echo esc_attr( $type ); ?>"
											placeholder="<?php
											if ( 'author' === $type ) {
												echo esc_attr__( 'Articles written by {{author}}. {{author_bio}}', 'saman-seo' );
											} elseif ( 'date' === $type ) {
												echo esc_attr__( 'Browse our articles from {{date}}.', 'saman-seo' );
											} elseif ( 'search' === $type ) {
												echo esc_attr__( 'Search results for "{{search_term}}" on {{sitename}}.', 'saman-seo' );
											} elseif ( '404' === $type ) {
												echo esc_attr__( 'The page you are looking for could not be found.', 'saman-seo' );
											}
											?>"
										><?php echo esc_textarea( $settings['description_template'] ?? '' ); ?></textarea>
										<button type="button" class="button saman-seo-trigger-vars" data-target="SAMAN_SEO_archive_defaults[<?php echo esc_attr( $type ); ?>][description_template]">
											<span class="dashicons dashicons-editor-code"></span>
											<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
										</button>
									</div>
								</div>

							</div>
						</details>
					<?php endforeach; ?>
				</div>
			</div>

		<!-- Social Settings Tab -->
		<div id="saman-seo-tab-social" class="saman-seo-tab-panel">
			<?php include __DIR__ . '/components/social-settings-tab.php'; ?>
		</div>

	<!-- Social Cards Tab -->
	<div id="saman-seo-tab-social-cards" class="saman-seo-tab-panel">
		<?php include __DIR__ . '/components/social-cards-tab.php'; ?>
	</div>

			<div class="saman-seo-tabs__actions">
				<?php submit_button( __( 'Save Search Appearance Settings', 'saman-seo' ), 'primary', 'submit', false ); ?>
			</div>
		</div>
	</form>
</div>
