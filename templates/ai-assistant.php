<?php
/**
 * AI admin screen.
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

// Render top bar
\WPSEOPilot\Admin_Topbar::render( 'ai' );
?>
<div class="wrap wpseopilot-page wpseopilot-ai-page">

	<?php if ( isset( $_GET['samanlabs_seo_ai_reset'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'AI settings restored to defaults. Remember to save if you make further tweaks.', 'saman-labs-seo' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpseopilot-tabs" data-component="wpseopilot-tabs">
		<div class="nav-tab-wrapper wpseopilot-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'AI Assistant sections', 'saman-labs-seo' ); ?>">
			<button
				type="button"
				class="nav-tab nav-tab-active"
				id="wpseopilot-tab-link-ai-settings"
				role="tab"
				aria-selected="true"
				aria-controls="wpseopilot-tab-ai-settings"
				data-wpseopilot-tab="wpseopilot-tab-ai-settings"
			>
				<?php esc_html_e( 'Settings', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="wpseopilot-tab-link-custom-models"
				role="tab"
				aria-selected="false"
				aria-controls="wpseopilot-tab-custom-models"
				data-wpseopilot-tab="wpseopilot-tab-custom-models"
			>
				<?php esc_html_e( 'Custom Models', 'saman-labs-seo' ); ?>
			</button>
		</div>

		<!-- Settings Tab -->
		<div
			id="wpseopilot-tab-ai-settings"
			class="wpseopilot-tab-panel is-active"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-ai-settings"
		>
			<!-- OpenAI Connection Card -->
			<div class="wpseopilot-card">
				<div class="wpseopilot-card-header">
					<h2><?php esc_html_e( 'Connect OpenAI', 'saman-labs-seo' ); ?></h2>
					<p><?php esc_html_e( 'Your API key stays on this site and is only sent to OpenAI when you click an AI button inside the editor.', 'saman-labs-seo' ); ?></p>
				</div>
				<div class="wpseopilot-card-body">
					<form action="options.php" method="post">
						<?php settings_fields( 'samanlabs_seo_ai_key' ); ?>

						<div class="wpseopilot-form-row">
							<label for="samanlabs_seo_openai_api_key">
								<strong><?php esc_html_e( 'OpenAI API Key', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint">
									<?php esc_html_e( 'Create a key on platform.openai.com, paste it here, and save.', 'saman-labs-seo' ); ?>
								</span>
							</label>
							<input
								type="password"
								class="regular-text"
								id="samanlabs_seo_openai_api_key"
								name="samanlabs_seo_openai_api_key"
								value="<?php echo esc_attr( $api_key ); ?>"
								autocomplete="off"
								placeholder="sk-..."
							/>
						</div>

						<?php submit_button( __( 'Save API key', 'saman-labs-seo' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
			</div>

			<!-- Model & Prompt Tuning Card -->
			<div class="wpseopilot-card">
				<div class="wpseopilot-card-header">
					<h2><?php esc_html_e( 'Model & Prompt Tuning', 'saman-labs-seo' ); ?></h2>
					<p><?php esc_html_e( 'Choose your preferred OpenAI model and fine-tune the prompts used for titles and descriptions.', 'saman-labs-seo' ); ?></p>
				</div>
				<div class="wpseopilot-card-body">
					<div class="wpseopilot-card-toolbar">
						<span><?php esc_html_e( 'Need a clean slate?', 'saman-labs-seo' ); ?></span>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
							<?php wp_nonce_field( 'samanlabs_seo_ai_reset' ); ?>
							<input type="hidden" name="action" value="samanlabs_seo_ai_reset" />
							<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset to defaults', 'saman-labs-seo' ); ?></button>
						</form>
					</div>

					<form action="options.php" method="post">
						<?php settings_fields( 'samanlabs_seo_ai_tuning' ); ?>

						<div class="wpseopilot-form-row">
							<label for="samanlabs_seo_ai_model">
								<strong><?php esc_html_e( 'Model', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint">
									<?php esc_html_e( 'Pick the balance of quality, latency, and price that fits your workflow.', 'saman-labs-seo' ); ?>
								</span>
							</label>
							<select id="samanlabs_seo_ai_model" name="samanlabs_seo_ai_model" class="regular-text">
								<?php foreach ( $models as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="wpseopilot-form-row">
							<label for="samanlabs_seo_ai_prompt_system">
								<strong><?php esc_html_e( 'System Prompt', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint">
									<?php esc_html_e( 'Used for every request. Great place to enforce voice, POV, or formatting rules.', 'saman-labs-seo' ); ?>
								</span>
							</label>
							<textarea
								class="large-text code"
								rows="3"
								id="samanlabs_seo_ai_prompt_system"
								name="samanlabs_seo_ai_prompt_system"
							><?php echo esc_textarea( $prompt_system ); ?></textarea>
						</div>

						<div class="wpseopilot-form-row">
							<label for="samanlabs_seo_ai_prompt_title">
								<strong><?php esc_html_e( 'Title Instructions', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint">
									<?php esc_html_e( 'Customize how AI should craft titles — length, tone, keywords, emojis, etc.', 'saman-labs-seo' ); ?>
								</span>
							</label>
							<textarea
								class="large-text"
								rows="3"
								id="samanlabs_seo_ai_prompt_title"
								name="samanlabs_seo_ai_prompt_title"
							><?php echo esc_textarea( $prompt_title ); ?></textarea>
						</div>

						<div class="wpseopilot-form-row">
							<label for="samanlabs_seo_ai_prompt_description">
								<strong><?php esc_html_e( 'Description Instructions', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint">
									<?php esc_html_e( 'Control summary length, CTAs, tone, or localization hints for descriptions.', 'saman-labs-seo' ); ?>
								</span>
							</label>
							<textarea
								class="large-text"
								rows="3"
								id="samanlabs_seo_ai_prompt_description"
								name="samanlabs_seo_ai_prompt_description"
							><?php echo esc_textarea( $prompt_description ); ?></textarea>
						</div>

						<?php submit_button( __( 'Save AI settings', 'saman-labs-seo' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
			</div>

			<!-- How it Works Card -->
			<div class="wpseopilot-card">
				<div class="wpseopilot-card-header">
					<h2><?php esc_html_e( 'How It Works in the Editor', 'saman-labs-seo' ); ?></h2>
				</div>
				<div class="wpseopilot-card-body">
					<p><?php esc_html_e( 'Once a key is saved, every post type using WP SEO Pilot shows "AI title" and "AI description" buttons in both the classic meta box and Gutenberg sidebar. Suggestions are inserted instantly and can be edited like normal text.', 'saman-labs-seo' ); ?></p>

					<div class="wpseopilot-features-grid">
						<div class="wpseopilot-feature">
							<h3><?php esc_html_e( 'Clear Feedback in Real Time', 'saman-labs-seo' ); ?></h3>
							<p><?php esc_html_e( 'Easily create content that ranks and build lasting visibility through better organic performance.', 'saman-labs-seo' ); ?></p>
							<ul class="wpseopilot-feature-list">
								<li><?php esc_html_e( 'See actionable SEO feedback instantly', 'saman-labs-seo' ); ?></li>
								<li><?php esc_html_e( 'Improve readability and structure', 'saman-labs-seo' ); ?></li>
								<li><?php esc_html_e( 'Spot issues before publishing', 'saman-labs-seo' ); ?></li>
							</ul>
						</div>

						<div class="wpseopilot-feature">
							<h3><?php esc_html_e( 'Work Smarter with AI Guidance', 'saman-labs-seo' ); ?></h3>
							<p><?php esc_html_e( 'Spend less time writing boilerplate copy and more time growing your business.', 'saman-labs-seo' ); ?></p>
							<ul class="wpseopilot-feature-list">
								<li><?php esc_html_e( 'Use generative AI to create SEO titles & meta descriptions', 'saman-labs-seo' ); ?></li>
								<li><?php esc_html_e( 'Get AI-powered suggestions tailored to your tone', 'saman-labs-seo' ); ?></li>
								<li><?php esc_html_e( 'Summarize content quickly for clarity', 'saman-labs-seo' ); ?></li>
							</ul>
						</div>

						<div class="wpseopilot-feature">
							<h3><?php esc_html_e( 'Privacy & Security', 'saman-labs-seo' ); ?></h3>
							<ul class="wpseopilot-feature-list">
								<li><?php esc_html_e( 'Buttons remain hidden if no API key is present', 'saman-labs-seo' ); ?></li>
								<li><?php esc_html_e( 'Requests include the content summary, URL, and any prompt customizations', 'saman-labs-seo' ); ?></li>
								<li><?php esc_html_e( 'Nothing is stored externally — responses update the field you are editing', 'saman-labs-seo' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Custom Models Tab -->
		<div
			id="wpseopilot-tab-custom-models"
			class="wpseopilot-tab-panel"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-custom-models"
		>
			<div class="wpseopilot-card">
				<div class="wpseopilot-card-header">
					<h2><?php esc_html_e( 'Custom AI Models', 'saman-labs-seo' ); ?></h2>
					<p><?php esc_html_e( 'Connect and configure custom AI models with your own API endpoints.', 'saman-labs-seo' ); ?></p>
				</div>
				<div class="wpseopilot-card-body">
					<div class="wpseopilot-coming-soon">
						<div class="wpseopilot-coming-soon-icon">
							<span class="dashicons dashicons-lightbulb"></span>
						</div>
						<h3><?php esc_html_e( 'Coming Soon', 'saman-labs-seo' ); ?></h3>
						<p><?php esc_html_e( 'This feature is currently in development and will be available in an upcoming release.', 'saman-labs-seo' ); ?></p>

						<div class="wpseopilot-coming-soon-features">
							<h4><?php esc_html_e( 'What to expect:', 'saman-labs-seo' ); ?></h4>
							<ul>
								<li>
									<span class="dashicons dashicons-yes"></span>
									<?php esc_html_e( 'Add custom AI model endpoints (OpenAI-compatible APIs)', 'saman-labs-seo' ); ?>
								</li>
								<li>
									<span class="dashicons dashicons-yes"></span>
									<?php esc_html_e( 'Configure API keys for different providers (Anthropic, Google, etc.)', 'saman-labs-seo' ); ?>
								</li>
								<li>
									<span class="dashicons dashicons-yes"></span>
									<?php esc_html_e( 'Set custom model parameters (temperature, max tokens, etc.)', 'saman-labs-seo' ); ?>
								</li>
								<li>
									<span class="dashicons dashicons-yes"></span>
									<?php esc_html_e( 'Use local AI models via Ollama or similar services', 'saman-labs-seo' ); ?>
								</li>
								<li>
									<span class="dashicons dashicons-yes"></span>
									<?php esc_html_e( 'Choose different models for titles vs descriptions', 'saman-labs-seo' ); ?>
								</li>
							</ul>
						</div>

						<div class="wpseopilot-coming-soon-cta">
							<p><?php esc_html_e( 'Want to be notified when this feature launches?', 'saman-labs-seo' ); ?></p>
							<a href="https://github.com/jhd3197/WP-SEO-Pilot" class="button button-primary" target="_blank" rel="noopener">
								<?php esc_html_e( 'Follow on GitHub', 'saman-labs-seo' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>
