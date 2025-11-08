<?php
/**
 * Post type defaults template.
 *
 * @var array $post_types
 * @var array $post_type_templates
 * @var array $post_type_descriptions
 * @var array $post_type_keywords
 * @var array $post_type_settings
 * @var array $taxonomy_settings
 * @var array $archive_settings
 * @var array $taxonomies
 * @var WPSEOPilot\Service\Settings $this
 *
 * @package WPSEOPilot
 */

call_user_func(
	static function ( $post_types, $post_type_templates, $post_type_descriptions, $post_type_keywords, $post_type_settings, $taxonomy_settings, $archive_settings, $taxonomies, $settings_instance ) {
		$schema_pages    = $settings_instance->get_schema_page_options();
		$schema_articles = $settings_instance->get_schema_article_options();
		$archive_items   = [
			'author' => __( 'Author archives', 'wp-seo-pilot' ),
			'date'   => __( 'Date archives', 'wp-seo-pilot' ),
			'search' => __( 'Search results', 'wp-seo-pilot' ),
		];
		?>
<div class="wrap wpseopilot-settings">
	<h1><?php esc_html_e( 'WP SEO Pilot — Search Appearance', 'wp-seo-pilot' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Control how your post types, taxonomies, and archives appear in search. These values act as defaults whenever editors leave fields blank.', 'wp-seo-pilot' ); ?>
	</p>

	<form action="options.php" method="post" class="wpseopilot-search-defaults">
		<?php settings_fields( 'wpseopilot_homepage' ); ?>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Homepage Defaults', 'wp-seo-pilot' ); ?></h2>
			<p><?php esc_html_e( 'Set the title and description that appear when visitors find your homepage in search results.', 'wp-seo-pilot' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="wpseopilot_homepage_title"><?php esc_html_e( 'SEO title', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="wpseopilot_homepage_title" name="wpseopilot_homepage_title" value="<?php echo esc_attr( get_option( 'wpseopilot_homepage_title' ) ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_homepage_description"><?php esc_html_e( 'Meta description', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<textarea class="large-text" rows="3" id="wpseopilot_homepage_description" name="wpseopilot_homepage_description"><?php echo esc_textarea( get_option( 'wpseopilot_homepage_description' ) ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_homepage_keywords"><?php esc_html_e( 'Keywords', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="wpseopilot_homepage_keywords" name="wpseopilot_homepage_keywords" value="<?php echo esc_attr( get_option( 'wpseopilot_homepage_keywords' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional comma-separated keywords for the homepage meta tag.', 'wp-seo-pilot' ); ?></p>
					</td>
				</tr>
			</table>
		</section>

		<?php submit_button( __( 'Save SEO defaults', 'wp-seo-pilot' ) ); ?>
	</form>

	<section class="wpseopilot-card">
		<h2><?php esc_html_e( 'Content Types', 'wp-seo-pilot' ); ?></h2>
		<p><?php esc_html_e( 'Decide whether each post type should appear in search, expose SEO controls to editors, and define fallback metadata.', 'wp-seo-pilot' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'wpseopilot_post_types' ); ?>
			<?php foreach ( $post_types as $slug => $object ) : ?>
				<?php
				$label = $object->labels->name ?: $object->label ?: ucfirst( $slug );
				$template = $post_type_templates[ $slug ] ?? '';
				$description = $post_type_descriptions[ $slug ] ?? '';
				$keywords = $post_type_keywords[ $slug ] ?? '';
				$settings = wp_parse_args(
					$post_type_settings[ $slug ] ?? [],
					[
						'show_search'    => '1',
						'show_seo'       => '1',
						'schema_page'    => 'WebPage',
						'schema_article' => 'Article',
						'analysis_fields' => '',
					]
				);
				?>
				<details class="wpseopilot-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="wpseopilot-type-slug"><?php echo esc_html( $slug ); ?></span>
					</summary>
					<div class="wpseopilot-accordion__body">
						<div class="wpseopilot-flex">
							<label>
								<strong><?php esc_html_e( 'Show in search results?', 'wp-seo-pilot' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="1" <?php checked( $settings['show_search'], '1' ); ?> />
									<span><?php esc_html_e( 'Yes', 'wp-seo-pilot' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="0" <?php checked( $settings['show_search'], '0' ); ?> />
									<span><?php esc_html_e( 'No', 'wp-seo-pilot' ); ?></span>
								</label>
							</label>
							<label>
								<strong><?php esc_html_e( 'Show SEO settings to editors?', 'wp-seo-pilot' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="1" <?php checked( $settings['show_seo'], '1' ); ?> />
									<span><?php esc_html_e( 'Show', 'wp-seo-pilot' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="0" <?php checked( $settings['show_seo'], '0' ); ?> />
									<span><?php esc_html_e( 'Hide', 'wp-seo-pilot' ); ?></span>
								</label>
							</label>
						</div>

						<label for="wpseopilot_template_<?php echo esc_attr( $slug ); ?>">
							<strong><?php esc_html_e( 'SEO title template', 'wp-seo-pilot' ); ?></strong>
						</label>
						<input type="text" class="regular-text" id="wpseopilot_template_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_title_templates[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $template ); ?>" />
						<p class="description">
							<?php
							/* translators: %post_title%, %site_title%, %tagline%, %post_author% are template placeholders. */
							esc_html_e( 'Available tags: %post_title%, %site_title%, %tagline%, %post_author%', 'wp-seo-pilot' );
							?>
						</p>

						<label for="wpseopilot_desc_<?php echo esc_attr( $slug ); ?>">
							<strong><?php esc_html_e( 'Meta description', 'wp-seo-pilot' ); ?></strong>
						</label>
						<textarea class="large-text" rows="3" id="wpseopilot_desc_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_meta_descriptions[<?php echo esc_attr( $slug ); ?>]"><?php echo esc_textarea( $description ); ?></textarea>

						<label for="wpseopilot_keywords_<?php echo esc_attr( $slug ); ?>">
							<strong><?php esc_html_e( 'Default keywords (optional)', 'wp-seo-pilot' ); ?></strong>
						</label>
						<input type="text" class="regular-text" id="wpseopilot_keywords_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_keywords[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $keywords ); ?>" />

						<div class="wpseopilot-flex">
							<label>
								<strong><?php esc_html_e( 'Default page schema type', 'wp-seo-pilot' ); ?></strong><br />
								<select name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][schema_page]">
									<?php foreach ( $schema_pages as $value => $text ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['schema_page'], $value ); ?>>
											<?php echo esc_html( $text ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
							<label>
								<strong><?php esc_html_e( 'Default article type', 'wp-seo-pilot' ); ?></strong><br />
								<select name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][schema_article]">
									<?php foreach ( $schema_articles as $value => $text ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['schema_article'], $value ); ?>>
											<?php echo esc_html( $text ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>

						<label for="wpseopilot_analysis_<?php echo esc_attr( $slug ); ?>">
							<strong><?php esc_html_e( 'Custom fields to analyse (comma separated)', 'wp-seo-pilot' ); ?></strong>
						</label>
						<input type="text" class="regular-text" id="wpseopilot_analysis_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][analysis_fields]" value="<?php echo esc_attr( $settings['analysis_fields'] ); ?>" />
					</div>
				</details>
			<?php endforeach; ?>

			<?php submit_button( __( 'Save post type defaults', 'wp-seo-pilot' ) ); ?>
		</form>
	</section>

	<section class="wpseopilot-card">
		<h2><?php esc_html_e( 'Taxonomies', 'wp-seo-pilot' ); ?></h2>
		<p><?php esc_html_e( 'Configure how category, tag, and custom taxonomy archives behave in search results.', 'wp-seo-pilot' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'wpseopilot_taxonomies' ); ?>
			<?php foreach ( $taxonomies as $slug => $taxonomy ) : ?>
				<?php
				$label = $taxonomy->labels->name ?: $taxonomy->label ?: ucfirst( $slug );
				$settings = wp_parse_args(
					$taxonomy_settings[ $slug ] ?? [],
					[
						'show_search' => '1',
						'show_seo'    => '1',
						'title'       => '',
						'description' => '',
					]
				);
				?>
				<details class="wpseopilot-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="wpseopilot-type-slug"><?php echo esc_html( $slug ); ?></span>
					</summary>
					<div class="wpseopilot-accordion__body">
						<div class="wpseopilot-flex">
							<label>
								<strong><?php esc_html_e( 'Show in search results?', 'wp-seo-pilot' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="1" <?php checked( $settings['show_search'], '1' ); ?> />
									<span><?php esc_html_e( 'Yes', 'wp-seo-pilot' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="0" <?php checked( $settings['show_search'], '0' ); ?> />
									<span><?php esc_html_e( 'No', 'wp-seo-pilot' ); ?></span>
								</label>
							</label>
							<label>
								<strong><?php esc_html_e( 'Show SEO settings?', 'wp-seo-pilot' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="1" <?php checked( $settings['show_seo'], '1' ); ?> />
									<span><?php esc_html_e( 'Show', 'wp-seo-pilot' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="0" <?php checked( $settings['show_seo'], '0' ); ?> />
									<span><?php esc_html_e( 'Hide', 'wp-seo-pilot' ); ?></span>
								</label>
							</label>
						</div>
						<label for="wpseopilot_tax_title_<?php echo esc_attr( $slug ); ?>">
							<strong><?php esc_html_e( 'SEO title', 'wp-seo-pilot' ); ?></strong>
						</label>
						<input type="text" class="regular-text" id="wpseopilot_tax_title_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][title]" value="<?php echo esc_attr( $settings['title'] ); ?>" />

						<label for="wpseopilot_tax_desc_<?php echo esc_attr( $slug ); ?>">
							<strong><?php esc_html_e( 'Meta description', 'wp-seo-pilot' ); ?></strong>
						</label>
						<textarea class="large-text" rows="3" id="wpseopilot_tax_desc_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][description]"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
					</div>
				</details>
			<?php endforeach; ?>

			<?php submit_button( __( 'Save taxonomy settings', 'wp-seo-pilot' ) ); ?>
		</form>
	</section>

	<section class="wpseopilot-card">
		<h2><?php esc_html_e( 'Archives & Special Templates', 'wp-seo-pilot' ); ?></h2>
		<p><?php esc_html_e( 'Control author archives, date archives, and built-in search pages.', 'wp-seo-pilot' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'wpseopilot_archives' ); ?>
			<?php foreach ( $archive_items as $key => $label ) : ?>
				<?php
				$settings = wp_parse_args(
					$archive_settings[ $key ] ?? [],
					[
						'show'        => '1',
						'title'       => '',
						'description' => '',
					]
				);
				?>
				<details class="wpseopilot-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="wpseopilot-type-slug"><?php echo esc_html( $key ); ?></span>
					</summary>
					<div class="wpseopilot-accordion__body">
						<label>
							<strong><?php esc_html_e( 'Show in search results?', 'wp-seo-pilot' ); ?></strong><br />
							<label class="wpseopilot-toggle">
								<input type="radio" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][show]" value="1" <?php checked( $settings['show'], '1' ); ?> />
								<span><?php esc_html_e( 'Yes', 'wp-seo-pilot' ); ?></span>
							</label>
							<label class="wpseopilot-toggle">
								<input type="radio" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][show]" value="0" <?php checked( $settings['show'], '0' ); ?> />
								<span><?php esc_html_e( 'No', 'wp-seo-pilot' ); ?></span>
							</label>
						</label>
						<label for="wpseopilot_archive_title_<?php echo esc_attr( $key ); ?>">
							<strong><?php esc_html_e( 'SEO title', 'wp-seo-pilot' ); ?></strong>
						</label>
						<input type="text" class="regular-text" id="wpseopilot_archive_title_<?php echo esc_attr( $key ); ?>" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][title]" value="<?php echo esc_attr( $settings['title'] ); ?>" />

						<label for="wpseopilot_archive_desc_<?php echo esc_attr( $key ); ?>">
							<strong><?php esc_html_e( 'Meta description', 'wp-seo-pilot' ); ?></strong>
						</label>
						<textarea class="large-text" rows="3" id="wpseopilot_archive_desc_<?php echo esc_attr( $key ); ?>" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][description]"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
					</div>
				</details>
			<?php endforeach; ?>

			<?php submit_button( __( 'Save archive settings', 'wp-seo-pilot' ) ); ?>
		</form>
	</section>
</div>
<?php
	},
	$post_types,
	$post_type_templates,
	$post_type_descriptions,
	$post_type_keywords,
	$post_type_settings,
	$taxonomy_settings,
	$archive_settings,
	$taxonomies,
	$this
);
