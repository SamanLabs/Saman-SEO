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
			'author' => __( 'Author archives', 'saman-labs-seo' ),
			'date'   => __( 'Date archives', 'saman-labs-seo' ),
			'search' => __( 'Search results', 'saman-labs-seo' ),
		];

		// Render top bar
		\WPSEOPilot\Admin_Topbar::render( 'types' );
		?>
<div class="wrap wpseopilot-page wpseopilot-settings">
	<form action="options.php" method="post" class="wpseopilot-search-defaults">
		<?php settings_fields( 'wpseopilot_search_appearance' ); ?>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Homepage Defaults', 'saman-labs-seo' ); ?></h2>
			<p><?php esc_html_e( 'Set the title and description that appear when visitors find your homepage in search results.', 'saman-labs-seo' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_homepage_title"><?php esc_html_e( 'SEO title', 'saman-labs-seo' ); ?></label>
						</div>
					</th>
					<td>
						<div class="wpseopilot-flex-input">
							<input type="text" class="regular-text" id="wpseopilot_homepage_title" name="wpseopilot_homepage_title" value="<?php echo esc_attr( get_option( 'wpseopilot_homepage_title' ) ); ?>" data-context="global" />
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_homepage_title">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_homepage_title">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<div class="wpseopilot-preview"></div>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_homepage_description"><?php esc_html_e( 'Meta description', 'saman-labs-seo' ); ?></label>
						</div>
					</th>
					<td>
						<div class="wpseopilot-flex-input">
							<textarea class="large-text" rows="3" id="wpseopilot_homepage_description" name="wpseopilot_homepage_description" data-context="global"><?php echo esc_textarea( get_option( 'wpseopilot_homepage_description' ) ); ?></textarea>
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_homepage_description">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_homepage_description">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<div class="wpseopilot-preview"></div>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_homepage_keywords"><?php esc_html_e( 'Keywords', 'saman-labs-seo' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="wpseopilot_homepage_keywords" name="wpseopilot_homepage_keywords" value="<?php echo esc_attr( get_option( 'wpseopilot_homepage_keywords' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional comma-separated keywords for the homepage meta tag.', 'saman-labs-seo' ); ?></p>
					</td>
				</tr>
			</table>
		</section>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Content Types', 'saman-labs-seo' ); ?></h2>
			<p><?php esc_html_e( 'Decide whether each post type should appear in search, expose SEO controls to editors, and define fallback metadata.', 'saman-labs-seo' ); ?></p>
			
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
				// Determine context
				$context_key = 'post_type:' . $slug;
				?>
				<details class="wpseopilot-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="wpseopilot-type-slug"><?php echo esc_html( $slug ); ?></span>
					</summary>
					<div class="wpseopilot-accordion__body">
						<div class="wpseopilot-flex">
							<label>
								<strong><?php esc_html_e( 'Show in search results?', 'saman-labs-seo' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="1" <?php checked( $settings['show_search'], '1' ); ?> />
									<span><?php esc_html_e( 'Yes', 'saman-labs-seo' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="0" <?php checked( $settings['show_search'], '0' ); ?> />
									<span><?php esc_html_e( 'No', 'saman-labs-seo' ); ?></span>
								</label>
							</label>
							<label>
								<strong><?php esc_html_e( 'Show SEO settings to editors?', 'saman-labs-seo' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="1" <?php checked( $settings['show_seo'], '1' ); ?> />
									<span><?php esc_html_e( 'Show', 'saman-labs-seo' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="0" <?php checked( $settings['show_seo'], '0' ); ?> />
									<span><?php esc_html_e( 'Hide', 'saman-labs-seo' ); ?></span>
								</label>
							</label>
						</div>

						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_template_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'SEO title template', 'saman-labs-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_template_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_template_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<input type="text" class="regular-text" id="wpseopilot_template_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_title_templates[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $template ); ?>" data-context="<?php echo esc_attr($context_key); ?>" />
						<div class="wpseopilot-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>
						
						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_desc_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'Meta description', 'saman-labs-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<textarea class="large-text" rows="3" id="wpseopilot_desc_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_meta_descriptions[<?php echo esc_attr( $slug ); ?>]" data-context="<?php echo esc_attr($context_key); ?>"><?php echo esc_textarea( $description ); ?></textarea>
						<div class="wpseopilot-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>

						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_keywords_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'Default keywords (optional)', 'saman-labs-seo' ); ?></strong>
							</label>
						</div>
						<input type="text" class="regular-text" id="wpseopilot_keywords_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_keywords[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $keywords ); ?>" />

						<div class="wpseopilot-flex">
							<label>
								<strong><?php esc_html_e( 'Default page schema type', 'saman-labs-seo' ); ?></strong><br />
								<select name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][schema_page]">
									<?php foreach ( $schema_pages as $value => $text ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['schema_page'], $value ); ?>>
											<?php echo esc_html( $text ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
							<label>
								<strong><?php esc_html_e( 'Default article type', 'saman-labs-seo' ); ?></strong><br />
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
							<strong><?php esc_html_e( 'Custom fields to analyse (comma separated)', 'saman-labs-seo' ); ?></strong>
						</label>
						<input type="text" class="regular-text" id="wpseopilot_analysis_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_post_type_settings[<?php echo esc_attr( $slug ); ?>][analysis_fields]" value="<?php echo esc_attr( $settings['analysis_fields'] ); ?>" />
					</div>
				</details>
			<?php endforeach; ?>
		</section>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Taxonomies', 'saman-labs-seo' ); ?></h2>
			<p><?php esc_html_e( 'Configure how category, tag, and custom taxonomy archives behave in search results.', 'saman-labs-seo' ); ?></p>
			
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
				$context_key = 'taxonomy';
				?>
				<details class="wpseopilot-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="wpseopilot-type-slug"><?php echo esc_html( $slug ); ?></span>
					</summary>
					<div class="wpseopilot-accordion__body">
						<div class="wpseopilot-flex">
							<label>
								<strong><?php esc_html_e( 'Show in search results?', 'saman-labs-seo' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="1" <?php checked( $settings['show_search'], '1' ); ?> />
									<span><?php esc_html_e( 'Yes', 'saman-labs-seo' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="0" <?php checked( $settings['show_search'], '0' ); ?> />
									<span><?php esc_html_e( 'No', 'saman-labs-seo' ); ?></span>
								</label>
							</label>
							<label>
								<strong><?php esc_html_e( 'Show SEO settings?', 'saman-labs-seo' ); ?></strong><br />
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="1" <?php checked( $settings['show_seo'], '1' ); ?> />
									<span><?php esc_html_e( 'Show', 'saman-labs-seo' ); ?></span>
								</label>
								<label class="wpseopilot-toggle">
									<input type="radio" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="0" <?php checked( $settings['show_seo'], '0' ); ?> />
									<span><?php esc_html_e( 'Hide', 'saman-labs-seo' ); ?></span>
								</label>
							</label>
						</div>

						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_tax_title_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'SEO title', 'saman-labs-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_tax_title_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_tax_title_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<input type="text" class="regular-text" id="wpseopilot_tax_title_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][title]" value="<?php echo esc_attr( $settings['title'] ); ?>" data-context="<?php echo esc_attr($context_key); ?>" />
						<div class="wpseopilot-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>

						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_tax_desc_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'Meta description', 'saman-labs-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_tax_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_tax_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<textarea class="large-text" rows="3" id="wpseopilot_tax_desc_<?php echo esc_attr( $slug ); ?>" name="wpseopilot_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][description]" data-context="<?php echo esc_attr($context_key); ?>"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
						<div class="wpseopilot-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>
					</div>
				</details>
			<?php endforeach; ?>
		</section>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Archives & Special Templates', 'saman-labs-seo' ); ?></h2>
			<p><?php esc_html_e( 'Control author archives, date archives, and built-in search pages.', 'saman-labs-seo' ); ?></p>
			
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
				$context_key = ($key === 'author') ? 'author' : 'archive'; 
				?>
				<details class="wpseopilot-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="wpseopilot-type-slug"><?php echo esc_html( $key ); ?></span>
					</summary>
					<div class="wpseopilot-accordion__body">
						<label>
							<strong><?php esc_html_e( 'Show in search results?', 'saman-labs-seo' ); ?></strong><br />
							<label class="wpseopilot-toggle">
								<input type="radio" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][show]" value="1" <?php checked( $settings['show'], '1' ); ?> />
								<span><?php esc_html_e( 'Yes', 'saman-labs-seo' ); ?></span>
							</label>
							<label class="wpseopilot-toggle">
								<input type="radio" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][show]" value="0" <?php checked( $settings['show'], '0' ); ?> />
								<span><?php esc_html_e( 'No', 'saman-labs-seo' ); ?></span>
							</label>
						</label>

						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_archive_title_<?php echo esc_attr( $key ); ?>">
								<strong><?php esc_html_e( 'SEO title', 'saman-labs-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_archive_title_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_archive_title_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<input type="text" class="regular-text" id="wpseopilot_archive_title_<?php echo esc_attr( $key ); ?>" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][title]" value="<?php echo esc_attr( $settings['title'] ); ?>" data-context="<?php echo esc_attr($context_key); ?>" />
						<div class="wpseopilot-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>

						<div class="wpseopilot-flex-label">
							<label for="wpseopilot_archive_desc_<?php echo esc_attr( $key ); ?>">
								<strong><?php esc_html_e( 'Meta description', 'saman-labs-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small wpseopilot-trigger-vars" data-target="wpseopilot_archive_desc_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
							</button>
							<button type="button" class="button button-small wpseopilot-trigger-preview" data-target="wpseopilot_archive_desc_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Preview', 'saman-labs-seo' ); ?>
							</button>
						</div>
						<textarea class="large-text" rows="3" id="wpseopilot_archive_desc_<?php echo esc_attr( $key ); ?>" name="wpseopilot_archive_settings[<?php echo esc_attr( $key ); ?>][description]" data-context="<?php echo esc_attr($context_key); ?>"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
						<div class="wpseopilot-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>
					</div>
				</details>
			<?php endforeach; ?>
		</section>

		<?php submit_button( __( 'Save Changes', 'saman-labs-seo' ) ); ?>
	</form>
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
