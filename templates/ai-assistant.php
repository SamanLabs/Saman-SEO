<?php
/**
 * AI Assistant admin screen.
 *
 * @var string $api_key
 * @var string $model
 * @var array  $models
 * @var string $prompt_system
 * @var string $prompt_title
 * @var string $prompt_description
 *
 * @package WPSEOPilot
 */

?>
<div class="wrap wpseopilot-settings wpseopilot-ai">
	<div class="wpseopilot-ai__hero">
		<h1><?php esc_html_e( 'WP SEO Pilot — AI Assistant', 'wp-seo-pilot' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Speed up titles, descriptions, and on-page polish with customizable AI guidance.', 'wp-seo-pilot' ); ?></p>
	</div>

	<div class="wpseopilot-card wpseopilot-ai__grid">
		<div>
			<h2><?php esc_html_e( 'Clear feedback in real time', 'wp-seo-pilot' ); ?></h2>
			<p><?php esc_html_e( 'Easily create content that ranks and build lasting visibility through better organic performance.', 'wp-seo-pilot' ); ?></p>
			<ul class="wpseopilot-list">
				<li><?php esc_html_e( 'See actionable SEO feedback instantly.', 'wp-seo-pilot' ); ?></li>
				<li><?php esc_html_e( 'Improve readability and structure.', 'wp-seo-pilot' ); ?></li>
				<li><?php esc_html_e( 'Spot issues before publishing.', 'wp-seo-pilot' ); ?></li>
			</ul>
		</div>
		<div>
			<h2><?php esc_html_e( 'Work smarter with AI guidance', 'wp-seo-pilot' ); ?></h2>
			<p><?php esc_html_e( 'Spend less time writing boilerplate copy and more time growing your business.', 'wp-seo-pilot' ); ?></p>
			<ul class="wpseopilot-list">
				<li><?php esc_html_e( 'Use generative AI to create SEO titles & meta descriptions.', 'wp-seo-pilot' ); ?></li>
				<li><?php esc_html_e( 'Get AI-powered suggestions tailored to your tone.', 'wp-seo-pilot' ); ?></li>
				<li><?php esc_html_e( 'Summarize content quickly for clarity.', 'wp-seo-pilot' ); ?></li>
			</ul>
		</div>
		<div>
			<h2><?php esc_html_e( 'More ways to stay ahead', 'wp-seo-pilot' ); ?></h2>
			<ul class="wpseopilot-list">
				<li><?php esc_html_e( 'Internal linking suggestions strengthen site architecture.', 'wp-seo-pilot' ); ?></li>
				<li><?php esc_html_e( 'Preview social cards before they go live.', 'wp-seo-pilot' ); ?></li>
				<li><?php esc_html_e( 'Guide editors with built-in SEO recommendations.', 'wp-seo-pilot' ); ?></li>
			</ul>
		</div>
	</div>

	<?php if ( isset( $_GET['wpseopilot_ai_reset'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'AI settings restored to defaults. Remember to save if you make further tweaks.', 'wp-seo-pilot' ); ?></p>
		</div>
	<?php endif; ?>

	<section class="wpseopilot-card">
		<h2><?php esc_html_e( 'Connect OpenAI', 'wp-seo-pilot' ); ?></h2>
		<p><?php esc_html_e( 'Your API key stays on this site and is only sent to OpenAI when you click an AI button inside the editor.', 'wp-seo-pilot' ); ?></p>
		<form action="options.php" method="post" class="wpseopilot-ai__form">
			<?php settings_fields( 'wpseopilot_ai_key' ); ?>
			<label for="wpseopilot_openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'wp-seo-pilot' ); ?></label>
			<input type="password" class="regular-text" id="wpseopilot_openai_api_key" name="wpseopilot_openai_api_key" value="<?php echo esc_attr( $api_key ); ?>" autocomplete="off" placeholder="sk-..." />
			<p class="description">
				<?php esc_html_e( 'Create a key on platform.openai.com, paste it here, and save. Rotate keys any time — editors will immediately see the new settings.', 'wp-seo-pilot' ); ?>
			</p>
			<?php submit_button( __( 'Save API key', 'wp-seo-pilot' ) ); ?>
		</form>
	</section>

	<section class="wpseopilot-card">
		<h2><?php esc_html_e( 'Model & prompt tuning', 'wp-seo-pilot' ); ?></h2>
		<p><?php esc_html_e( 'Choose your preferred OpenAI model and fine-tune the prompts used for titles and descriptions. Helpful for brand voice, tone, or localization requirements.', 'wp-seo-pilot' ); ?></p>
		<div class="wpseopilot-ai__toolbar">
			<span><?php esc_html_e( 'Need a clean slate?', 'wp-seo-pilot' ); ?></span>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wpseopilot_ai_reset' ); ?>
				<input type="hidden" name="action" value="wpseopilot_ai_reset" />
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset to defaults', 'wp-seo-pilot' ); ?></button>
			</form>
		</div>
		<form action="options.php" method="post" class="wpseopilot-ai__form wpseopilot-ai__form--grid">
			<?php settings_fields( 'wpseopilot_ai_tuning' ); ?>

			<div class="wpseopilot-field">
				<label for="wpseopilot_ai_model"><?php esc_html_e( 'Model', 'wp-seo-pilot' ); ?></label>
				<select id="wpseopilot_ai_model" name="wpseopilot_ai_model">
					<?php foreach ( $models as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Pick the balance of quality, latency, and price that fits your workflow.', 'wp-seo-pilot' ); ?></p>
			</div>

			<div class="wpseopilot-field">
				<label for="wpseopilot_ai_prompt_system"><?php esc_html_e( 'System prompt', 'wp-seo-pilot' ); ?></label>
				<textarea class="large-text code" rows="3" id="wpseopilot_ai_prompt_system" name="wpseopilot_ai_prompt_system"><?php echo esc_textarea( $prompt_system ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Used for every request. Great place to enforce voice, POV, or formatting rules.', 'wp-seo-pilot' ); ?></p>
			</div>

			<div class="wpseopilot-field">
				<label for="wpseopilot_ai_prompt_title"><?php esc_html_e( 'Title instructions', 'wp-seo-pilot' ); ?></label>
				<textarea class="large-text" rows="3" id="wpseopilot_ai_prompt_title" name="wpseopilot_ai_prompt_title"><?php echo esc_textarea( $prompt_title ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Customize how AI should craft titles — length, tone, keywords, emojis, etc.', 'wp-seo-pilot' ); ?></p>
			</div>

			<div class="wpseopilot-field">
				<label for="wpseopilot_ai_prompt_description"><?php esc_html_e( 'Description instructions', 'wp-seo-pilot' ); ?></label>
				<textarea class="large-text" rows="3" id="wpseopilot_ai_prompt_description" name="wpseopilot_ai_prompt_description"><?php echo esc_textarea( $prompt_description ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Control summary length, CTAs, tone, or localization hints for descriptions.', 'wp-seo-pilot' ); ?></p>
			</div>

			<?php submit_button( __( 'Save AI settings', 'wp-seo-pilot' ) ); ?>
		</form>
	</section>

	<section class="wpseopilot-card">
		<h2><?php esc_html_e( 'How it works in the editor', 'wp-seo-pilot' ); ?></h2>
		<p><?php esc_html_e( 'Once a key is saved, every post type using WP SEO Pilot shows “AI title” and “AI description” buttons in both the classic meta box and Gutenberg sidebar. Suggestions are inserted instantly and can be edited like normal text.', 'wp-seo-pilot' ); ?></p>
		<ul class="wpseopilot-list">
			<li><?php esc_html_e( 'Buttons remain hidden if no API key is present.', 'wp-seo-pilot' ); ?></li>
			<li><?php esc_html_e( 'Requests include the content summary, URL, and any prompt customizations.', 'wp-seo-pilot' ); ?></li>
			<li><?php esc_html_e( 'Nothing is stored externally — responses update the field you are editing.', 'wp-seo-pilot' ); ?></li>
		</ul>
	</section>
</div>
