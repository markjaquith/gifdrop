<?php
defined( 'WPINC' ) or die;
?>
<div class="wrap">
	<h2><?php echo esc_html( $GLOBALS['title'] ); ?></h2>

	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="gifdrop-postbox">
		<input type="hidden" name="action" value="gifdrop-save" />
		<?php wp_nonce_field( self::NONCE ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="gifdrop-pages"><?php _e( 'GifDrop Location', 'gifdrop' ); ?></label></th>
				<td class="gifdrop-select-pages-section">
					<?php echo home_url( '/' ); ?><input type="text" id="gifdrop-path" name="gifdrop_path" value="<?php echo esc_attr( $this->get_option( 'path' ) ); ?>" />
					<p class="description"><?php _e( 'The URL on your site where you want your gif collection to be available.', 'gifdrop' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __('Save Changes', 'gifdrop' ), 'primary', 'submit', true ); ?>
	</form>
</div>
