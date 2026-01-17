<?php
/**
 * Sitemap Settings Page Template
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

// Render top bar
\WPSEOPilot\Admin_Topbar::render( 'sitemap', '', [
	[
		'type'   => 'button',
		'label'  => __( 'View Sitemap', 'saman-labs-seo' ),
		'url'    => home_url( '/sitemap_index.xml' ),
		'target' => '_blank',
		'class'  => 'button-secondary',
	],
	[
		'type'   => 'button',
		'label'  => __( 'Open llm.txt', 'saman-labs-seo' ),
		'url'    => home_url( '/llm.txt' ),
		'target' => '_blank',
		'class'  => 'button-secondary',
	],
] );
?>

<div class="wrap samanlabs-seo-page samanlabs-seo-sitemap-page">
	<div class="samanlabs-seo-tabs" data-component="samanlabs-seo-tabs">
		<div class="nav-tab-wrapper samanlabs-seo-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Sitemap sections', 'saman-labs-seo' ); ?>">
			<button
				type="button"
				class="nav-tab nav-tab-active"
				id="samanlabs-seo-tab-link-sitemap"
				role="tab"
				aria-selected="true"
				aria-controls="samanlabs-seo-tab-sitemap"
				data-samanlabs-seo-tab="samanlabs-seo-tab-sitemap"
			>
				<?php esc_html_e( 'XML Sitemap', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="samanlabs-seo-tab-link-llm"
				role="tab"
				aria-selected="false"
				aria-controls="samanlabs-seo-tab-llm"
				data-samanlabs-seo-tab="samanlabs-seo-tab-llm"
			>
				<?php esc_html_e( 'LLM.txt', 'saman-labs-seo' ); ?>
			</button>
		</div>

		<!-- XML Sitemap Tab -->
		<div
			id="samanlabs-seo-tab-sitemap"
			class="samanlabs-seo-tab-panel is-active"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-sitemap"
		>
			<form method="post" action="">
				<?php wp_nonce_field( 'samanlabs_seo_sitemap_settings' ); ?>

				<div class="samanlabs-seo-settings-grid">
					<!-- Main Settings Column -->
					<div class="samanlabs-seo-settings-main">

						<!-- XML Sitemap Configuration Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h2><?php esc_html_e( 'XML Sitemap Configuration', 'saman-labs-seo' ); ?></h2>
								<p><?php esc_html_e( 'Control which content appears in your XML sitemap and how it is organized.', 'saman-labs-seo' ); ?></p>
							</div>
							<div class="samanlabs-seo-card-body">

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'Automatic Updates', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<select name="samanlabs_seo_sitemap_schedule_updates" class="samanlabs-seo-select">
											<?php foreach ( $schedule_options as $value => $label ) : ?>
												<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $schedule_updates, $value ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Automatically regenerate sitemap on a schedule.', 'saman-labs-seo' ); ?></span>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'Max URLs Per Page', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<input type="number"
											   name="samanlabs_seo_sitemap_max_urls"
											   value="<?php echo esc_attr( $max_urls ); ?>"
											   min="1"
											   max="50000"
											   class="samanlabs-seo-input small">
										<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Maximum number of URLs per sitemap page (recommended: 1000).', 'saman-labs-seo' ); ?></span>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'Options', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="enable-index" name="samanlabs_seo_sitemap_enable_index" value="1" <?php checked( $enable_index, '1' ); ?>>
											<label for="enable-index"><?php esc_html_e( 'Enable sitemap indexes for better organization', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="dynamic-gen" name="samanlabs_seo_sitemap_dynamic_generation" value="1" <?php checked( $dynamic_generation, '1' ); ?>>
											<label for="dynamic-gen"><?php esc_html_e( 'Dynamically generate sitemap on-demand', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="exclude-images" name="samanlabs_seo_sitemap_exclude_images" value="1" <?php checked( $exclude_images, '1' ); ?>>
											<label for="exclude-images"><?php esc_html_e( 'Exclude images from sitemap entries', 'saman-labs-seo' ); ?></label>
										</div>
									</div>
								</div>

							</div>
						</div>

						<!-- Content Types Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h2><?php esc_html_e( 'Content Types', 'saman-labs-seo' ); ?></h2>
								<p><?php esc_html_e( 'Select which post types and taxonomies should be included in your sitemap.', 'saman-labs-seo' ); ?></p>
							</div>
							<div class="samanlabs-seo-card-body">

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><strong><?php esc_html_e( 'Post Types', 'saman-labs-seo' ); ?></strong></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-type-grid">
											<?php foreach ( $post_types as $post_type ) : ?>
												<div class="samanlabs-seo-toggle">
													<input type="checkbox"
														   id="pt-<?php echo esc_attr( $post_type->name ); ?>"
														   name="samanlabs_seo_sitemap_post_types[]"
														   value="<?php echo esc_attr( $post_type->name ); ?>"
														   <?php checked( in_array( $post_type->name, $selected_post_types, true ) ); ?>>
													<label for="pt-<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->label ); ?></label>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><strong><?php esc_html_e( 'Taxonomies', 'saman-labs-seo' ); ?></strong></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-type-grid">
											<?php foreach ( $taxonomies as $taxonomy ) : ?>
												<div class="samanlabs-seo-toggle">
													<input type="checkbox"
														   id="tax-<?php echo esc_attr( $taxonomy->name ); ?>"
														   name="samanlabs_seo_sitemap_taxonomies[]"
														   value="<?php echo esc_attr( $taxonomy->name ); ?>"
														   <?php checked( in_array( $taxonomy->name, $selected_taxonomies, true ) ); ?>>
													<label for="tax-<?php echo esc_attr( $taxonomy->name ); ?>"><?php echo esc_html( $taxonomy->label ); ?></label>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'Archives', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="include-author" name="samanlabs_seo_sitemap_include_author_pages" value="1" <?php checked( $include_author, '1' ); ?>>
											<label for="include-author"><?php esc_html_e( 'Include author archive pages', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="include-date" name="samanlabs_seo_sitemap_include_date_archives" value="1" <?php checked( $include_date, '1' ); ?>>
											<label for="include-date"><?php esc_html_e( 'Include date archive pages', 'saman-labs-seo' ); ?></label>
										</div>
									</div>
								</div>

							</div>
						</div>

						<!-- Additional Sitemaps Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h2><?php esc_html_e( 'Additional Sitemaps', 'saman-labs-seo' ); ?></h2>
								<p><?php esc_html_e( 'Enable specialized sitemaps for RSS feeds and Google News.', 'saman-labs-seo' ); ?></p>
							</div>
							<div class="samanlabs-seo-card-body">

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'RSS Sitemap', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="enable-rss" name="samanlabs_seo_sitemap_enable_rss" value="1" <?php checked( $enable_rss, '1' ); ?>>
											<label for="enable-rss"><?php esc_html_e( 'Generate RSS sitemap with latest 50 posts', 'saman-labs-seo' ); ?></label>
										</div>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'Google News', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="enable-news" name="samanlabs_seo_sitemap_enable_google_news" value="1" <?php checked( $enable_google_news, '1' ); ?>>
											<label for="enable-news"><strong><?php esc_html_e( 'Enable Google News sitemap', 'saman-labs-seo' ); ?></strong></label>
										</div>

										<div style="margin-top: 12px;">
											<input type="text"
												   name="samanlabs_seo_sitemap_google_news_name"
												   value="<?php echo esc_attr( $google_news_name ); ?>"
												   placeholder="<?php esc_attr_e( 'Publication Name', 'saman-labs-seo' ); ?>"
												   class="samanlabs-seo-input">
											<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'The name of your publication for Google News.', 'saman-labs-seo' ); ?></span>
										</div>

										<div style="margin-top: 12px;">
											<div class="samanlabs-seo-checkbox-list">
												<?php foreach ( $post_types as $post_type ) : ?>
													<label>
														<input type="checkbox"
															   name="samanlabs_seo_sitemap_google_news_post_types[]"
															   value="<?php echo esc_attr( $post_type->name ); ?>"
															   <?php checked( in_array( $post_type->name, $google_news_post_types, true ) ); ?>>
														<?php echo esc_html( $post_type->label ); ?>
													</label>
												<?php endforeach; ?>
											</div>
											<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Post types to include in Google News sitemap.', 'saman-labs-seo' ); ?></span>
										</div>
									</div>
								</div>

							</div>
						</div>

						<!-- Additional Pages Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h2><?php esc_html_e( 'Additional Pages', 'saman-labs-seo' ); ?></h2>
								<p><?php esc_html_e( 'Add custom URLs to your sitemap that are not managed by WordPress.', 'saman-labs-seo' ); ?></p>
							</div>
							<div class="samanlabs-seo-card-body">
								<div class="samanlabs-seo-additional-pages">
									<div id="additional-pages-container">
										<?php if ( ! empty( $additional_pages ) ) : ?>
											<?php foreach ( $additional_pages as $index => $page ) : ?>
												<div class="additional-page-row">
													<input type="url"
														   name="samanlabs_seo_sitemap_additional_pages[<?php echo esc_attr( $index ); ?>][url]"
														   value="<?php echo esc_url( $page['url'] ?? '' ); ?>"
														   placeholder="https://example.com/page"
														   class="samanlabs-seo-input">
													<input type="text"
														   name="samanlabs_seo_sitemap_additional_pages[<?php echo esc_attr( $index ); ?>][priority]"
														   value="<?php echo esc_attr( $page['priority'] ?? '0.5' ); ?>"
														   placeholder="0.5">
													<button type="button" class="button remove-page"><?php esc_html_e( 'Remove', 'saman-labs-seo' ); ?></button>
												</div>
											<?php endforeach; ?>
										<?php endif; ?>
									</div>
									<button type="button" class="button" id="add-additional-page"><?php esc_html_e( 'Add Page', 'saman-labs-seo' ); ?></button>
									<span class="samanlabs-seo-helper-text" style="display: block; margin-top: 8px;"><?php esc_html_e( 'Add custom URLs with their priority (0.0 to 1.0).', 'saman-labs-seo' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Save Button -->
						<p class="submit" style="margin-top: 20px;">
							<input type="submit" name="samanlabs_seo_sitemap_submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'saman-labs-seo' ); ?>">
							<button type="button" class="button" id="regenerate-sitemap"><?php esc_html_e( 'Regenerate Now', 'saman-labs-seo' ); ?></button>
						</p>

					</div>

					<!-- Sidebar Column -->
					<div class="samanlabs-seo-settings-sidebar">

						<!-- Sitemap URLs Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h3><?php esc_html_e( 'Your Sitemaps', 'saman-labs-seo' ); ?></h3>
								<p><?php esc_html_e( 'Access your generated sitemaps below.', 'saman-labs-seo' ); ?></p>
							</div>
							<div class="samanlabs-seo-card-body">
								<div class="samanlabs-seo-sitemap-urls">
									<div class="sitemap-url-item">
										<strong><?php esc_html_e( 'Main Index:', 'saman-labs-seo' ); ?></strong>
										<a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>" target="_blank">
											<?php echo esc_html( home_url( '/sitemap_index.xml' ) ); ?>
										</a>
									</div>
									<?php if ( '1' === $enable_rss ) : ?>
									<div class="sitemap-url-item">
										<strong><?php esc_html_e( 'RSS:', 'saman-labs-seo' ); ?></strong>
										<a href="<?php echo esc_url( home_url( '/sitemap-rss.xml' ) ); ?>" target="_blank">
											<?php echo esc_html( home_url( '/sitemap-rss.xml' ) ); ?>
										</a>
									</div>
									<?php endif; ?>
									<?php if ( '1' === $enable_google_news ) : ?>
									<div class="sitemap-url-item">
										<strong><?php esc_html_e( 'Google News:', 'saman-labs-seo' ); ?></strong>
										<a href="<?php echo esc_url( home_url( '/sitemap-news.xml' ) ); ?>" target="_blank">
											<?php echo esc_html( home_url( '/sitemap-news.xml' ) ); ?>
										</a>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Info Card -->
						<div class="samanlabs-seo-info-card">
							<p><strong><?php esc_html_e( 'Tip:', 'saman-labs-seo' ); ?></strong> <?php esc_html_e( 'Submit your sitemap to Google Search Console and Bing Webmaster Tools for better indexing.', 'saman-labs-seo' ); ?></p>
						</div>

					</div>
				</div>
			</form>
		</div>

		<!-- LLM.txt Tab -->
		<div
			id="samanlabs-seo-tab-llm"
			class="samanlabs-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-llm"
		>
			<form method="post" action="">
				<?php wp_nonce_field( 'samanlabs_seo_llm_txt_settings' ); ?>

				<div class="samanlabs-seo-settings-grid">
					<!-- Main Settings Column -->
					<div class="samanlabs-seo-settings-main">

						<!-- LLM.txt Introduction Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h2><?php esc_html_e( 'LLM.txt', 'saman-labs-seo' ); ?></h2>
								<p><?php esc_html_e( 'The llm.txt is a specialized file designed to help AI engines (such as language models) discover the content on your site more easily. Similar to how XML sitemaps assist search engines, the llm.txt file guides AI crawlers by providing important details about the available site content, improving visibility and discoverability across AI-driven tools.', 'saman-labs-seo' ); ?>
								<a href="https://llmstxt.org/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More →', 'saman-labs-seo' ); ?></a>
								</p>
							</div>
							<div class="samanlabs-seo-card-body">
								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'Enable llm.txt', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="enable-llm-txt" name="samanlabs_seo_enable_llm_txt" value="1" <?php checked( $llm_enabled, '1' ); ?>>
											<label for="enable-llm-txt"><?php esc_html_e( 'Generate an llm.txt file to help AI engines discover the content on your site more easily.', 'saman-labs-seo' ); ?></label>
										</div>
										<?php if ( '1' === $llm_enabled ) : ?>
										<div style="margin-top: 12px;">
											<a href="<?php echo esc_url( home_url( '/llm.txt' ) ); ?>" target="_blank" class="button button-secondary">
												<?php esc_html_e( 'Open llm.txt', 'saman-labs-seo' ); ?> ↗
											</a>
										</div>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>

						<?php if ( '1' === $llm_enabled ) : ?>
						<!-- LLM.txt Settings Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h2><?php esc_html_e( 'LLM.txt Settings', 'saman-labs-seo' ); ?></h2>
								<p><?php esc_html_e( 'Customize how your llm.txt file is generated and what content it includes.', 'saman-labs-seo' ); ?></p>
							</div>
							<div class="samanlabs-seo-card-body">
								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label for="llm-txt-title"><?php esc_html_e( 'Title', 'saman-labs-seo' ); ?></label>
										<p class="samanlabs-seo-helper-text"><?php esc_html_e( 'The main title displayed at the top of your llm.txt file.', 'saman-labs-seo' ); ?></p>
									</div>
									<div class="samanlabs-seo-form-control">
										<input type="text"
											   id="llm-txt-title"
											   name="samanlabs_seo_llm_txt_title"
											   value="<?php echo esc_attr( $llm_title ); ?>"
											   class="samanlabs-seo-input"
											   placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
										<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Defaults to your site name if left empty.', 'saman-labs-seo' ); ?></span>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label for="llm-txt-description"><?php esc_html_e( 'Description', 'saman-labs-seo' ); ?></label>
										<p class="samanlabs-seo-helper-text"><?php esc_html_e( 'A brief description of your site displayed below the title.', 'saman-labs-seo' ); ?></p>
									</div>
									<div class="samanlabs-seo-form-control">
										<textarea
											id="llm-txt-description"
											name="samanlabs_seo_llm_txt_description"
											rows="3"
											class="samanlabs-seo-textarea"
											placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( $llm_description ); ?></textarea>
										<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Defaults to your site tagline if left empty.', 'saman-labs-seo' ); ?></span>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label for="llm-txt-posts-per-type"><?php esc_html_e( 'Max Posts Per Type', 'saman-labs-seo' ); ?></label>
										<p class="samanlabs-seo-helper-text"><?php esc_html_e( 'Limit the number of posts included per post type.', 'saman-labs-seo' ); ?></p>
									</div>
									<div class="samanlabs-seo-form-control">
										<input type="number"
											   id="llm-txt-posts-per-type"
											   name="samanlabs_seo_llm_txt_posts_per_type"
											   value="<?php echo esc_attr( $llm_posts_per_type ); ?>"
											   min="1"
											   max="500"
											   class="samanlabs-seo-input small">
										<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Set between 1-500 posts per post type (recommended: 50).', 'saman-labs-seo' ); ?></span>
									</div>
								</div>

								<div class="samanlabs-seo-form-row">
									<div class="samanlabs-seo-form-label">
										<label><?php esc_html_e( 'Options', 'saman-labs-seo' ); ?></label>
									</div>
									<div class="samanlabs-seo-form-control">
										<div class="samanlabs-seo-toggle">
											<input type="checkbox" id="include-excerpt" name="samanlabs_seo_llm_txt_include_excerpt" value="1" <?php checked( $llm_include_excerpt, '1' ); ?>>
											<label for="include-excerpt"><?php esc_html_e( 'Include post excerpts/descriptions in llm.txt', 'saman-labs-seo' ); ?></label>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Content Preview Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h2><?php esc_html_e( 'Content Types Included', 'saman-labs-seo' ); ?></h2>
								<p><?php esc_html_e( 'The following post types will be included in your llm.txt file.', 'saman-labs-seo' ); ?></p>
							</div>
							<div class="samanlabs-seo-card-body">
								<div class="samanlabs-seo-post-types-list">
									<?php foreach ( $post_types as $post_type ) : ?>
										<?php
										$count = wp_count_posts( $post_type->name );
										$published = $count->publish ?? 0;
										$will_include = min( $published, $llm_posts_per_type );
										?>
										<div class="samanlabs-seo-post-type-item">
											<div class="samanlabs-seo-post-type-icon">
												<span class="dashicons dashicons-admin-post"></span>
											</div>
											<div class="samanlabs-seo-post-type-info">
												<strong><?php echo esc_html( $post_type->label ); ?></strong>
												<span class="samanlabs-seo-helper-text">
													<?php
													/* translators: 1: number of posts to include, 2: total number of published posts */
													echo esc_html( sprintf( __( 'Including %1$d of %2$d published posts', 'saman-labs-seo' ), $will_include, $published ) );
													?>
												</span>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>

						<!-- Save Button -->
						<p class="submit" style="margin-top: 20px;">
							<input type="submit" name="samanlabs_seo_llm_txt_submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'saman-labs-seo' ); ?>">
						</p>
						<?php endif; ?>

					</div>

					<!-- Sidebar -->
					<div class="samanlabs-seo-settings-sidebar">
						<?php if ( '1' === $llm_enabled ) : ?>
						<!-- Quick Info Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h3><?php esc_html_e( 'Quick Info', 'saman-labs-seo' ); ?></h3>
							</div>
							<div class="samanlabs-seo-card-body">
								<p class="samanlabs-seo-helper-text">
									<?php esc_html_e( 'Your llm.txt file is accessible at:', 'saman-labs-seo' ); ?>
								</p>
								<p>
									<code style="word-break: break-all; display: block; padding: 8px; background: #f6f7f7; border-radius: 4px;">
										<?php echo esc_html( home_url( '/llm.txt' ) ); ?>
									</code>
								</p>
								<p class="samanlabs-seo-helper-text" style="margin-top: 12px;">
									<?php
									echo sprintf(
										/* translators: %s: link to permalinks settings */
										esc_html__( 'Note: If the file is not accessible, visit %s and save to flush rewrite rules.', 'saman-labs-seo' ),
										'<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . esc_html__( 'Permalink Settings', 'saman-labs-seo' ) . '</a>'
									);
									?>
								</p>
							</div>
						</div>

						<!-- About llm.txt Card -->
						<div class="samanlabs-seo-card">
							<div class="samanlabs-seo-card-header">
								<h3><?php esc_html_e( 'About llm.txt', 'saman-labs-seo' ); ?></h3>
							</div>
							<div class="samanlabs-seo-card-body">
								<p class="samanlabs-seo-helper-text">
									<?php esc_html_e( 'The llm.txt file helps AI language models like ChatGPT, Claude, and others discover and understand your content structure.', 'saman-labs-seo' ); ?>
								</p>
								<p class="samanlabs-seo-helper-text">
									<?php esc_html_e( 'This can improve how AI systems reference and cite your content when answering questions.', 'saman-labs-seo' ); ?>
								</p>
								<a href="https://llmstxt.org/" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="margin-top: 12px;">
									<?php esc_html_e( 'Learn More About llm.txt', 'saman-labs-seo' ); ?> ↗
								</a>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<style>
.samanlabs-seo-post-types-list {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.samanlabs-seo-post-type-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px;
	background: #f6f7f7;
	border-radius: 6px;
}

.samanlabs-seo-post-type-icon {
	flex-shrink: 0;
	width: 40px;
	height: 40px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #fff;
	border-radius: 6px;
}

.samanlabs-seo-post-type-icon .dashicons {
	color: #2271b1;
	width: 24px;
	height: 24px;
	font-size: 24px;
}

.samanlabs-seo-post-type-info {
	display: flex;
	flex-direction: column;
	gap: 2px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Add additional page
	$('#add-additional-page').on('click', function() {
		var index = $('#additional-pages-container .additional-page-row').length;
		var html = '<div class="additional-page-row">' +
			'<input type="url" name="samanlabs_seo_sitemap_additional_pages[' + index + '][url]" placeholder="https://example.com/page" class="samanlabs-seo-input">' +
			'<input type="text" name="samanlabs_seo_sitemap_additional_pages[' + index + '][priority]" value="0.5" placeholder="0.5">' +
			'<button type="button" class="button remove-page"><?php esc_html_e( 'Remove', 'saman-labs-seo' ); ?></button>' +
			'</div>';
		$('#additional-pages-container').append(html);
	});

	// Remove additional page
	$(document).on('click', '.remove-page', function() {
		$(this).closest('.additional-page-row').remove();
	});

	// Regenerate sitemap
	$('#regenerate-sitemap').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true).text(WPSEOPilotSitemap.strings.regenerating);

		$.post(WPSEOPilotSitemap.ajax_url, {
			action: 'samanlabs_seo_regenerate_sitemap',
			nonce: WPSEOPilotSitemap.nonce
		}, function(response) {
			if (response.success) {
				alert(WPSEOPilotSitemap.strings.success);
			} else {
				alert(WPSEOPilotSitemap.strings.error);
			}
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Regenerate Now', 'saman-labs-seo' ); ?>');
		}).fail(function() {
			alert(WPSEOPilotSitemap.strings.error);
			$btn.prop('disabled', false).text('<?php esc_html_e( 'Regenerate Now', 'saman-labs-seo' ); ?>');
		});
	});
});
</script>
