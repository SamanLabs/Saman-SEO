<?php
use WPSEOPilot\Service\Post_Meta;
/**
 * Bulk editor template.
 *
 * @var WP_Query $query
 *
 * @package WPSEOPilot
 */

?>
<div class="wrap wpseopilot-bulk">
	<h1><?php esc_html_e( 'Bulk SEO Editor', 'wp-seo-pilot' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpseopilot_bulk_save' ); ?>
		<input type="hidden" name="action" value="wpseopilot_bulk_save" />

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Post', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Meta title', 'wp-seo-pilot' ); ?></th>
					<th><?php esc_html_e( 'Meta description', 'wp-seo-pilot' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
							$meta = (array) get_post_meta( get_the_ID(), Post_Meta::META_KEY, true );
						?>
						<tr>
							<td>
								<strong><a href="<?php the_permalink(); ?>" target="_blank" rel="noopener noreferrer"><?php the_title(); ?></a></strong>
								<p class="description"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></p>
							</td>
							<td>
								<input type="text" class="widefat" name="wpseopilot_bulk[<?php echo esc_attr( get_the_ID() ); ?>][title]" value="<?php echo esc_attr( $meta['title'] ?? '' ); ?>" maxlength="160" />
							</td>
							<td>
								<textarea class="widefat" name="wpseopilot_bulk[<?php echo esc_attr( get_the_ID() ); ?>][description]" rows="2" maxlength="320"><?php echo esc_textarea( $meta['description'] ?? '' ); ?></textarea>
							</td>
						</tr>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<tr>
						<td colspan="3"><?php esc_html_e( 'No posts found.', 'wp-seo-pilot' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

		<?php submit_button( __( 'Save changes', 'wp-seo-pilot' ) ); ?>
	</form>

	<?php if ( $query->max_num_pages > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php
				echo paginate_links(
					[
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => __( '&laquo;', 'wp-seo-pilot' ),
						'next_text' => __( '&raquo;', 'wp-seo-pilot' ),
						'total'     => $query->max_num_pages,
						'current'   => max( 1, get_query_var( 'paged', 1 ) ),
					]
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
