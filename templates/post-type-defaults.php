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
 * @var Saman\SEO\Service\Settings $this
 *
 * @package Saman\SEO
 */

call_user_func(
	static function ( $post_types, $post_type_templates, $post_type_descriptions, $post_type_keywords, $post_type_settings, $taxonomy_settings, $archive_settings, $taxonomies, $settings_instance ) {
		$schema_pages    = $settings_instance->get_schema_page_options();
		$schema_articles = $settings_instance->get_schema_article_options();
		$archive_items   = [
			'author' => __( 'Author archives', 'saman-seo' ),
			'date'   => __( 'Date archives', 'saman-seo' ),
			'search' => __( 'Search results', 'saman-seo' ),
		];

		// Render top bar
		\Saman\SEO\Admin_Topbar::render( 'types' );
		?>
<div class="wrap saman-seo-page saman-seo-settings">
	<form action="options.php" method="post" class="saman-seo-search-defaults">
		<?php settings_fields( 'SAMAN_SEO_search_appearance' ); ?>

		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Homepage Defaults', 'saman-seo' ); ?></h2>
			<p><?php esc_html_e( 'Set the title and description that appear when visitors find your homepage in search results.', 'saman-seo' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_homepage_title"><?php esc_html_e( 'SEO title', 'saman-seo' ); ?></label>
						</div>
					</th>
					<td>
						<div class="saman-seo-flex-input">
							<input type="text" class="regular-text" id="SAMAN_SEO_homepage_title" name="SAMAN_SEO_homepage_title" value="<?php echo esc_attr( get_option( 'SAMAN_SEO_homepage_title' ) ); ?>" data-context="global" />
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_homepage_title">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_homepage_title">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<div class="saman-seo-preview"></div>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_homepage_description"><?php esc_html_e( 'Meta description', 'saman-seo' ); ?></label>
						</div>
					</th>
					<td>
						<div class="saman-seo-flex-input">
							<textarea class="large-text" rows="3" id="SAMAN_SEO_homepage_description" name="SAMAN_SEO_homepage_description" data-context="global"><?php echo esc_textarea( get_option( 'SAMAN_SEO_homepage_description' ) ); ?></textarea>
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_homepage_description">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_homepage_description">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<div class="saman-seo-preview"></div>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="SAMAN_SEO_homepage_keywords"><?php esc_html_e( 'Keywords', 'saman-seo' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="SAMAN_SEO_homepage_keywords" name="SAMAN_SEO_homepage_keywords" value="<?php echo esc_attr( get_option( 'SAMAN_SEO_homepage_keywords' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional comma-separated keywords for the homepage meta tag.', 'saman-seo' ); ?></p>
					</td>
				</tr>
			</table>
		</section>

		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Content Types', 'saman-seo' ); ?></h2>
			<p><?php esc_html_e( 'Decide whether each post type should appear in search, expose SEO controls to editors, and define fallback metadata.', 'saman-seo' ); ?></p>
			
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
				<details class="saman-seo-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="saman-seo-type-slug"><?php echo esc_html( $slug ); ?></span>
					</summary>
					<div class="saman-seo-accordion__body">
						<div class="saman-seo-flex">
							<label>
								<strong><?php esc_html_e( 'Show in search results?', 'saman-seo' ); ?></strong><br />
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="1" <?php checked( $settings['show_search'], '1' ); ?> />
									<span><?php esc_html_e( 'Yes', 'saman-seo' ); ?></span>
								</label>
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="0" <?php checked( $settings['show_search'], '0' ); ?> />
									<span><?php esc_html_e( 'No', 'saman-seo' ); ?></span>
								</label>
							</label>
							<label>
								<strong><?php esc_html_e( 'Show SEO settings to editors?', 'saman-seo' ); ?></strong><br />
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="1" <?php checked( $settings['show_seo'], '1' ); ?> />
									<span><?php esc_html_e( 'Show', 'saman-seo' ); ?></span>
								</label>
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_post_type_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="0" <?php checked( $settings['show_seo'], '0' ); ?> />
									<span><?php esc_html_e( 'Hide', 'saman-seo' ); ?></span>
								</label>
							</label>
						</div>

						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_template_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'SEO title template', 'saman-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_template_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_template_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<input type="text" class="regular-text" id="SAMAN_SEO_template_<?php echo esc_attr( $slug ); ?>" name="SAMAN_SEO_post_type_title_templates[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $template ); ?>" data-context="<?php echo esc_attr($context_key); ?>" />
						<div class="saman-seo-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>
						
						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_desc_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'Meta description', 'saman-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<textarea class="large-text" rows="3" id="SAMAN_SEO_desc_<?php echo esc_attr( $slug ); ?>" name="SAMAN_SEO_post_type_meta_descriptions[<?php echo esc_attr( $slug ); ?>]" data-context="<?php echo esc_attr($context_key); ?>"><?php echo esc_textarea( $description ); ?></textarea>
						<div class="saman-seo-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>

						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_keywords_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'Default keywords (optional)', 'saman-seo' ); ?></strong>
							</label>
						</div>
						<input type="text" class="regular-text" id="SAMAN_SEO_keywords_<?php echo esc_attr( $slug ); ?>" name="SAMAN_SEO_post_type_keywords[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $keywords ); ?>" />

						<div class="saman-seo-flex">
							<label>
								<strong><?php esc_html_e( 'Default page schema type', 'saman-seo' ); ?></strong><br />
								<select name="SAMAN_SEO_post_type_settings[<?php echo esc_attr( $slug ); ?>][schema_page]">
									<?php foreach ( $schema_pages as $value => $text ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['schema_page'], $value ); ?>>
											<?php echo esc_html( $text ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
							<label>
								<strong><?php esc_html_e( 'Default article type', 'saman-seo' ); ?></strong><br />
								<select name="SAMAN_SEO_post_type_settings[<?php echo esc_attr( $slug ); ?>][schema_article]">
									<?php foreach ( $schema_articles as $value => $text ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['schema_article'], $value ); ?>>
											<?php echo esc_html( $text ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>

						<label for="SAMAN_SEO_analysis_<?php echo esc_attr( $slug ); ?>">
							<strong><?php esc_html_e( 'Custom fields to analyse (comma separated)', 'saman-seo' ); ?></strong>
						</label>
						<input type="text" class="regular-text" id="SAMAN_SEO_analysis_<?php echo esc_attr( $slug ); ?>" name="SAMAN_SEO_post_type_settings[<?php echo esc_attr( $slug ); ?>][analysis_fields]" value="<?php echo esc_attr( $settings['analysis_fields'] ); ?>" />
					</div>
				</details>
			<?php endforeach; ?>
		</section>

		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Taxonomies', 'saman-seo' ); ?></h2>
			<p><?php esc_html_e( 'Configure how category, tag, and custom taxonomy archives behave in search results.', 'saman-seo' ); ?></p>
			
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
				<details class="saman-seo-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="saman-seo-type-slug"><?php echo esc_html( $slug ); ?></span>
					</summary>
					<div class="saman-seo-accordion__body">
						<div class="saman-seo-flex">
							<label>
								<strong><?php esc_html_e( 'Show in search results?', 'saman-seo' ); ?></strong><br />
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="1" <?php checked( $settings['show_search'], '1' ); ?> />
									<span><?php esc_html_e( 'Yes', 'saman-seo' ); ?></span>
								</label>
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_search]" value="0" <?php checked( $settings['show_search'], '0' ); ?> />
									<span><?php esc_html_e( 'No', 'saman-seo' ); ?></span>
								</label>
							</label>
							<label>
								<strong><?php esc_html_e( 'Show SEO settings?', 'saman-seo' ); ?></strong><br />
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="1" <?php checked( $settings['show_seo'], '1' ); ?> />
									<span><?php esc_html_e( 'Show', 'saman-seo' ); ?></span>
								</label>
								<label class="saman-seo-toggle">
									<input type="radio" name="SAMAN_SEO_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][show_seo]" value="0" <?php checked( $settings['show_seo'], '0' ); ?> />
									<span><?php esc_html_e( 'Hide', 'saman-seo' ); ?></span>
								</label>
							</label>
						</div>

						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_tax_title_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'SEO title', 'saman-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_tax_title_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_tax_title_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<input type="text" class="regular-text" id="SAMAN_SEO_tax_title_<?php echo esc_attr( $slug ); ?>" name="SAMAN_SEO_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][title]" value="<?php echo esc_attr( $settings['title'] ); ?>" data-context="<?php echo esc_attr($context_key); ?>" />
						<div class="saman-seo-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>

						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_tax_desc_<?php echo esc_attr( $slug ); ?>">
								<strong><?php esc_html_e( 'Meta description', 'saman-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_tax_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_tax_desc_<?php echo esc_attr( $slug ); ?>">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<textarea class="large-text" rows="3" id="SAMAN_SEO_tax_desc_<?php echo esc_attr( $slug ); ?>" name="SAMAN_SEO_taxonomy_settings[<?php echo esc_attr( $slug ); ?>][description]" data-context="<?php echo esc_attr($context_key); ?>"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
						<div class="saman-seo-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>
					</div>
				</details>
			<?php endforeach; ?>
		</section>

		<section class="saman-seo-card">
			<h2><?php esc_html_e( 'Archives & Special Templates', 'saman-seo' ); ?></h2>
			<p><?php esc_html_e( 'Control author archives, date archives, and built-in search pages.', 'saman-seo' ); ?></p>
			
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
				<details class="saman-seo-accordion">
					<summary>
						<span><?php echo esc_html( $label ); ?></span>
						<span class="saman-seo-type-slug"><?php echo esc_html( $key ); ?></span>
					</summary>
					<div class="saman-seo-accordion__body">
						<label>
							<strong><?php esc_html_e( 'Show in search results?', 'saman-seo' ); ?></strong><br />
							<label class="saman-seo-toggle">
								<input type="radio" name="SAMAN_SEO_archive_settings[<?php echo esc_attr( $key ); ?>][show]" value="1" <?php checked( $settings['show'], '1' ); ?> />
								<span><?php esc_html_e( 'Yes', 'saman-seo' ); ?></span>
							</label>
							<label class="saman-seo-toggle">
								<input type="radio" name="SAMAN_SEO_archive_settings[<?php echo esc_attr( $key ); ?>][show]" value="0" <?php checked( $settings['show'], '0' ); ?> />
								<span><?php esc_html_e( 'No', 'saman-seo' ); ?></span>
							</label>
						</label>

						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_archive_title_<?php echo esc_attr( $key ); ?>">
								<strong><?php esc_html_e( 'SEO title', 'saman-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_archive_title_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_archive_title_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<input type="text" class="regular-text" id="SAMAN_SEO_archive_title_<?php echo esc_attr( $key ); ?>" name="SAMAN_SEO_archive_settings[<?php echo esc_attr( $key ); ?>][title]" value="<?php echo esc_attr( $settings['title'] ); ?>" data-context="<?php echo esc_attr($context_key); ?>" />
						<div class="saman-seo-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>

						<div class="saman-seo-flex-label">
							<label for="SAMAN_SEO_archive_desc_<?php echo esc_attr( $key ); ?>">
								<strong><?php esc_html_e( 'Meta description', 'saman-seo' ); ?></strong>
							</label>
							<button type="button" class="button button-small saman-seo-trigger-vars" data-target="SAMAN_SEO_archive_desc_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
							</button>
							<button type="button" class="button button-small saman-seo-trigger-preview" data-target="SAMAN_SEO_archive_desc_<?php echo esc_attr( $key ); ?>">
								<?php esc_html_e( 'Preview', 'saman-seo' ); ?>
							</button>
						</div>
						<textarea class="large-text" rows="3" id="SAMAN_SEO_archive_desc_<?php echo esc_attr( $key ); ?>" name="SAMAN_SEO_archive_settings[<?php echo esc_attr( $key ); ?>][description]" data-context="<?php echo esc_attr($context_key); ?>"><?php echo esc_textarea( $settings['description'] ); ?></textarea>
						<div class="saman-seo-preview" style="margin-top: 5px; color: #646970; font-style: italic;"></div>
					</div>
				</details>
			<?php endforeach; ?>
		</section>

		<?php submit_button( __( 'Save Changes', 'saman-seo' ) ); ?>
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
