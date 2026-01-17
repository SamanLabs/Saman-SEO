<?php
/**
 * Internal Linking Ã¢â‚¬â€ Add/Edit rule form.
 *
 * @package Saman\SEO
 */

$is_edit          = ! empty( $current_rule['id'] );
$form_action      = admin_url( 'admin-post.php' );
$form_heading     = $is_edit ? __( 'Edit rule', 'saman-seo' ) : __( 'Add rule', 'saman-seo' );
$keywords_value   = implode( "\n", $current_rule['keywords'] );
$category_value   = $current_rule['category'] ?? '';
$destination_type = $current_rule['destination']['type'] ?? 'post';
$destination_post = (int) ( $current_rule['destination']['post'] ?? 0 );
$destination_url  = $current_rule['destination']['url'] ?? '';
$destination_hint = '';

if ( 'post' === $destination_type && $destination_post ) {
	$post_obj = get_post( $destination_post );
	if ( $post_obj ) {
		$destination_hint = sprintf( '%1$s (%2$s)', get_the_title( $post_obj ), $post_obj->post_type );
	}
}

$scope_post_types = $current_rule['scope']['post_types'] ?? [];
if ( empty( $scope_post_types ) && ! $is_edit ) {
	$scope_post_types = array_values( array_intersect( [ 'post', 'page' ], array_keys( $post_types ) ) );
}

$heading_behavior = $current_rule['placement']['headings'] ?? 'none';
$heading_levels   = $current_rule['placement']['heading_levels'] ?? [];

$whitelist = $current_rule['scope']['whitelist'] ?? [];
$blacklist = $current_rule['scope']['blacklist'] ?? [];

$post_type_options = array_keys( $post_types );

?>
<div class="saman-seo-card saman-seo-links__rule">
	<header class="saman-seo-links__rule-hero">
		<div>
			<p class="saman-seo-links__pill"><?php esc_html_e( 'Internal Linking', 'saman-seo' ); ?></p>
			<h2><?php echo esc_html( $form_heading ); ?></h2>
			<p><?php esc_html_e( 'Build automated linking playbooks with rich targeting, controls, and live previews.', 'saman-seo' ); ?></p>
			<ul>
				<li><?php esc_html_e( 'Keyword-driven replacements with per-rule priorities.', 'saman-seo' ); ?></li>
				<li><?php esc_html_e( 'Category + UTM inheritance keeps tracking consistent.', 'saman-seo' ); ?></li>
				<li><?php esc_html_e( 'Placement + scope controls prevent overlinking.', 'saman-seo' ); ?></li>
			</ul>
		</div>
		<div class="saman-seo-links__rule-hero-meta">
			<div>
				<span><?php esc_html_e( 'Avg. links saved', 'saman-seo' ); ?></span>
				<strong>12 / post</strong>
			</div>
			<div>
				<span><?php esc_html_e( 'Rules running', 'saman-seo' ); ?></span>
				<strong><?php echo esc_html( number_format_i18n( max( 1, count( $all_rules ) ) ) ); ?></strong>
			</div>
			<a href="<?php echo esc_url( add_query_arg( [ 'tab' => 'rules' ], $page_url ) ); ?>" class="button button-secondary" aria-label="<?php esc_attr_e( 'Return to rule list', 'saman-seo' ); ?>">
				&larr; <?php esc_html_e( 'Back to rules', 'saman-seo' ); ?>
			</a>
		</div>
	</header>

	<div class="saman-seo-links__rule-guide">
		<div class="saman-seo-links__rule-steps">
			<div class="saman-seo-links__rule-step">
				<span class="saman-seo-links__rule-step-number">1</span>
				<div>
					<strong><?php esc_html_e( 'Define the match', 'saman-seo' ); ?></strong>
					<p><?php esc_html_e( 'Name the rule, choose a category, and list the keywords you want to transform.', 'saman-seo' ); ?></p>
				</div>
			</div>
			<div class="saman-seo-links__rule-step">
				<span class="saman-seo-links__rule-step-number">2</span>
				<div>
					<strong><?php esc_html_e( 'Pick a destination', 'saman-seo' ); ?></strong>
					<p><?php esc_html_e( 'Link to a post, page, or custom URL and add UTM tracking if needed.', 'saman-seo' ); ?></p>
				</div>
			</div>
			<div class="saman-seo-links__rule-step">
				<span class="saman-seo-links__rule-step-number">3</span>
				<div>
					<strong><?php esc_html_e( 'Control placement', 'saman-seo' ); ?></strong>
					<p><?php esc_html_e( 'Set limits, choose headings/blocks, scope post types, and preview the output.', 'saman-seo' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="saman-seo-links__rule-form" autocomplete="off">
		<?php wp_nonce_field( 'SAMAN_SEO_save_link_rule' ); ?>
		<input type="hidden" name="action" value="SAMAN_SEO_save_link_rule" />
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="rule[id]" value="<?php echo esc_attr( $current_rule['id'] ); ?>" />
			<input type="hidden" name="rule[created_at]" value="<?php echo esc_attr( $current_rule['created_at'] ?? time() ); ?>" />
		<?php endif; ?>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Basics', 'saman-seo' ); ?></h3>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'Internal Title', 'saman-seo' ); ?></span>
					<input type="text" class="regular-text" name="rule[title]" required value="<?php echo esc_attr( $current_rule['title'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., Services cluster links', 'saman-seo' ); ?>" />
					<p class="description"><?php esc_html_e( 'Only visible to admins.', 'saman-seo' ); ?></p>
				</label>

				<label>
					<span><?php esc_html_e( 'Category', 'saman-seo' ); ?></span>
					<select name="rule[category]" data-category-select>
						<option value=""><?php esc_html_e( 'None', 'saman-seo' ); ?></option>
						<?php foreach ( $categories as $category ) : ?>
							<option value="<?php echo esc_attr( $category['id'] ); ?>" <?php selected( $category_value, $category['id'] ); ?>>
								<?php echo esc_html( $category['name'] ); ?>
							</option>
						<?php endforeach; ?>
						<option value="__new__">+ <?php esc_html_e( 'New CategoryÃ¢â‚¬Â¦', 'saman-seo' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Group rules (e.g., Topic Clusters, Services). Also controls default UTMs and limits if set.', 'saman-seo' ); ?></p>
				</label>
			</div>

			<div class="saman-seo-links__new-category" data-new-category hidden>
				<h4><?php esc_html_e( 'Quick category', 'saman-seo' ); ?></h4>
				<div class="saman-seo-grid">
					<label>
						<span><?php esc_html_e( 'Name', 'saman-seo' ); ?></span>
						<input type="text" name="new_category[name]" />
					</label>
					<label>
						<span><?php esc_html_e( 'Color', 'saman-seo' ); ?></span>
						<input type="color" name="new_category[color]" value="<?php echo esc_attr( $category_default['color'] ?? '#4f46e5' ); ?>" />
					</label>
					<label>
						<span><?php esc_html_e( 'Default UTM template', 'saman-seo' ); ?></span>
						<select name="new_category[default_utm]">
							<option value=""><?php esc_html_e( 'None', 'saman-seo' ); ?></option>
							<?php foreach ( $utm_templates as $template ) : ?>
								<option value="<?php echo esc_attr( $template['id'] ); ?>"><?php echo esc_html( $template['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e( 'Category cap (per page)', 'saman-seo' ); ?></span>
						<input type="number" min="0" max="50" name="new_category[category_cap]" />
					</label>
				</div>
				<label>
					<span><?php esc_html_e( 'Description', 'saman-seo' ); ?></span>
					<textarea name="new_category[description]" rows="2"></textarea>
				</label>
			</div>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Destination', 'saman-seo' ); ?></h3>
			<div class="saman-seo-links__destination">
				<label class="saman-seo-links__choice">
					<input type="radio" name="rule[destination][type]" value="post" <?php checked( 'post', $destination_type ); ?> data-destination-toggle />
					<span><?php esc_html_e( 'WordPress content', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="radio" name="rule[destination][type]" value="url" <?php checked( 'url', $destination_type ); ?> data-destination-toggle />
					<span><?php esc_html_e( 'Custom URL', 'saman-seo' ); ?></span>
				</label>
			</div>
			<div class="saman-seo-links__destination-field" data-destination-field="post" <?php echo ( 'post' === $destination_type ) ? '' : 'hidden'; ?>>
				<input type="hidden" name="rule[destination][post]" value="<?php echo esc_attr( $destination_post ); ?>" data-destination-value />
				<label>
					<span><?php esc_html_e( 'Choose content', 'saman-seo' ); ?></span>
					<input type="text" data-destination-input placeholder="<?php esc_attr_e( 'Search by titleÃ¢â‚¬Â¦', 'saman-seo' ); ?>" value="<?php echo esc_attr( $destination_hint ); ?>" />
				</label>
				<p class="description"><?php esc_html_e( 'Pick one destination per rule.', 'saman-seo' ); ?></p>
				<div class="saman-seo-links__suggestions" data-destination-suggestions hidden></div>
			</div>
			<div class="saman-seo-links__destination-field" data-destination-field="url" <?php echo ( 'url' === $destination_type ) ? '' : 'hidden'; ?>>
				<label>
					<span><?php esc_html_e( 'URL', 'saman-seo' ); ?></span>
					<input type="url" name="rule[destination][url]" value="<?php echo esc_attr( $destination_url ); ?>" placeholder="https://example.com" />
				</label>
				<p class="description"><?php esc_html_e( 'Pick one destination per rule.', 'saman-seo' ); ?></p>
			</div>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Keywords', 'saman-seo' ); ?></h3>
			<div class="saman-seo-tag-input" data-tag-input>
				<input type="hidden" name="rule[keywords]" value="<?php echo esc_attr( $keywords_value ); ?>" data-tag-input-store />
				<div class="saman-seo-tag-input__list" data-tag-list></div>
				<input type="text" class="saman-seo-tag-input__field" placeholder="<?php esc_attr_e( 'Type keyword and press Enter', 'saman-seo' ); ?>" data-tag-input-field />
			</div>
			<p class="description"><?php esc_html_e( 'Use Enter to add each keyword. Exact phrase match; word boundaries recommended.', 'saman-seo' ); ?></p>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'UTMs', 'saman-seo' ); ?></h3>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'UTM Template', 'saman-seo' ); ?></span>
					<select name="rule[utm_template]">
						<option value="inherit" <?php selected( 'inherit', $current_rule['utm_template'] ?? 'inherit' ); ?>><?php esc_html_e( 'Inherit from Category', 'saman-seo' ); ?></option>
						<?php foreach ( $utm_templates as $template ) : ?>
							<option value="<?php echo esc_attr( $template['id'] ); ?>" <?php selected( $current_rule['utm_template'], $template['id'] ); ?>>
								<?php echo esc_html( $template['name'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>

				<fieldset>
					<legend><?php esc_html_e( 'Apply to', 'saman-seo' ); ?></legend>
					<label class="saman-seo-links__choice">
						<input type="radio" name="rule[utm_apply_to]" value="internal" <?php checked( 'internal', $current_rule['utm_apply_to'] ); ?> />
						<span><?php esc_html_e( 'Internal links only', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="radio" name="rule[utm_apply_to]" value="external" <?php checked( 'external', $current_rule['utm_apply_to'] ); ?> />
						<span><?php esc_html_e( 'External links only', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="radio" name="rule[utm_apply_to]" value="both" <?php checked( 'both', $current_rule['utm_apply_to'] ); ?> />
						<span><?php esc_html_e( 'Both', 'saman-seo' ); ?></span>
					</label>
					<p class="description"><?php esc_html_e( 'UTMs are appended for GA tracking. WordPress does not track clicks.', 'saman-seo' ); ?></p>
				</fieldset>
			</div>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Attributes', 'saman-seo' ); ?></h3>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'Link Title', 'saman-seo' ); ?></span>
					<input type="text" name="rule[attributes][title]" value="<?php echo esc_attr( $current_rule['attributes']['title'] ?? '' ); ?>" />
				</label>
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="rule[attributes][no_title]" value="1" <?php checked( ! empty( $current_rule['attributes']['no_title'] ) ); ?> />
					<span><?php esc_html_e( 'DonÃ¢â‚¬â„¢t use a link title', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="rule[attributes][nofollow]" value="1" <?php checked( ! empty( $current_rule['attributes']['nofollow'] ) ); ?> />
					<span><?php esc_html_e( 'Add rel="nofollow"', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="rule[attributes][new_tab]" value="1" <?php checked( ! empty( $current_rule['attributes']['new_tab'] ) ); ?> />
					<span><?php esc_html_e( 'Open in new tab (adds rel="noopener")', 'saman-seo' ); ?></span>
				</label>
			</div>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Limits & Priority', 'saman-seo' ); ?></h3>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'Number of links (max per page)', 'saman-seo' ); ?></span>
					<input type="number" min="0" max="50" name="rule[limits][max_page]" value="<?php echo esc_attr( $current_rule['limits']['max_page'] ?? 1 ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'Max per block (optional)', 'saman-seo' ); ?></span>
					<input type="number" min="0" max="50" name="rule[limits][max_block]" value="<?php echo esc_attr( $current_rule['limits']['max_block'] ?? '' ); ?>" />
				</label>
				<label>
					<span><?php esc_html_e( 'Priority', 'saman-seo' ); ?></span>
					<input type="number" name="rule[priority]" value="<?php echo esc_attr( $current_rule['priority'] ?? 10 ); ?>" />
					<p class="description"><?php esc_html_e( 'Higher runs first.', 'saman-seo' ); ?></p>
				</label>
				<label class="saman-seo-links__choice">
					<input type="hidden" name="rule[status]" value="inactive" />
					<input type="checkbox" name="rule[status]" value="active" <?php checked( ( $current_rule['status'] ?? 'active' ) === 'active' ); ?> />
					<span><?php esc_html_e( 'Status: Active', 'saman-seo' ); ?></span>
				</label>
			</div>
			<p class="description"><?php esc_html_e( 'Limit the number of automatic links per page and per block to avoid overlinking.', 'saman-seo' ); ?></p>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Placement', 'saman-seo' ); ?></h3>
			<div class="saman-seo-grid">
				<fieldset>
					<legend><?php esc_html_e( 'Apply in headings?', 'saman-seo' ); ?></legend>
					<label class="saman-seo-links__choice">
						<input type="radio" name="rule[placement][headings]" value="none" <?php checked( 'none', $heading_behavior ); ?> data-heading-toggle />
						<span><?php esc_html_e( 'None', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="radio" name="rule[placement][headings]" value="selected" <?php checked( 'selected', $heading_behavior ); ?> data-heading-toggle />
						<span><?php esc_html_e( 'Selected', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="radio" name="rule[placement][headings]" value="all" <?php checked( 'all', $heading_behavior ); ?> data-heading-toggle />
						<span><?php esc_html_e( 'All', 'saman-seo' ); ?></span>
					</label>
					<p class="description"><?php esc_html_e( 'Control whether links can appear in headings. For Selected, choose specific levels (H1Ã¢â‚¬â€œH6).', 'saman-seo' ); ?></p>
					<div class="saman-seo-links__heading-levels" data-heading-levels <?php echo ( 'selected' === $heading_behavior ) ? '' : 'hidden'; ?>>
						<?php foreach ( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] as $level ) : ?>
							<label class="saman-seo-links__choice">
								<input type="checkbox" name="rule[placement][heading_levels][]" value="<?php echo esc_attr( $level ); ?>" <?php checked( in_array( $level, $heading_levels, true ) ); ?> />
								<span><?php echo esc_html( strtoupper( $level ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<fieldset class="saman-seo-links__toggles">
					<label class="saman-seo-links__choice">
						<input type="checkbox" name="rule[placement][paragraphs]" value="1" <?php checked( ! empty( $current_rule['placement']['paragraphs'] ) ); ?> />
						<span><?php esc_html_e( 'Apply in paragraphs', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="checkbox" name="rule[placement][lists]" value="1" <?php checked( ! empty( $current_rule['placement']['lists'] ) ); ?> />
						<span><?php esc_html_e( 'Apply in lists', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="checkbox" name="rule[placement][captions]" value="1" <?php checked( ! empty( $current_rule['placement']['captions'] ) ); ?> />
						<span><?php esc_html_e( 'Apply in captions', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="checkbox" name="rule[placement][widgets]" value="1" <?php checked( ! empty( $current_rule['placement']['widgets'] ) ); ?> />
						<span><?php esc_html_e( 'Apply in widgets', 'saman-seo' ); ?></span>
					</label>
				</fieldset>
			</div>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Content Scope', 'saman-seo' ); ?></h3>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'Post types', 'saman-seo' ); ?></span>
					<select name="rule[scope][post_types][]" multiple size="5">
						<?php foreach ( $post_types as $type => $label ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( in_array( $type, $scope_post_types, true ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'Whitelist URLs', 'saman-seo' ); ?></span>
					<textarea name="rule[scope][whitelist]" rows="3" placeholder="https://example.com/services"><?php echo esc_textarea( implode( "\n", $whitelist ) ); ?></textarea>
				</label>
				<label>
					<span><?php esc_html_e( 'Blacklist URLs', 'saman-seo' ); ?></span>
					<textarea name="rule[scope][blacklist]" rows="3" placeholder="https://example.com/contact"><?php echo esc_textarea( implode( "\n", $blacklist ) ); ?></textarea>
				</label>
			</div>
			<p class="description"><?php esc_html_e( 'Whitelist = only these pages. Blacklist = never these pages.', 'saman-seo' ); ?></p>
		</section>

		<section class="saman-seo-links__section">
			<h3><?php esc_html_e( 'Preview / Test', 'saman-seo' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Preview simulates replacements without saving changes.', 'saman-seo' ); ?></p>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'Select a post', 'saman-seo' ); ?></span>
					<div class="saman-seo-links__preview-select" data-preview-target>
						<input type="hidden" data-preview-post />
						<input type="text" data-preview-input placeholder="<?php esc_attr_e( 'Search for contentÃ¢â‚¬Â¦', 'saman-seo' ); ?>" />
						<div class="saman-seo-links__suggestions" data-preview-suggestions hidden></div>
					</div>
				</label>
				<label>
					<span><?php esc_html_e( 'or Enter a URL', 'saman-seo' ); ?></span>
					<input type="url" data-preview-url placeholder="https://example.com/sample-post" />
				</label>
			</div>
			<button type="button" class="button" data-preview-run><?php esc_html_e( 'Run Preview', 'saman-seo' ); ?></button>
			<div class="saman-seo-links__preview" data-preview-output hidden>
				<div class="saman-seo-links__preview-status" data-preview-status></div>
				<pre data-preview-result></pre>
			</div>
		</section>

		<?php submit_button( $is_edit ? __( 'Update rule', 'saman-seo' ) : __( 'Save rule', 'saman-seo' ) ); ?>
	</form>
</div>
