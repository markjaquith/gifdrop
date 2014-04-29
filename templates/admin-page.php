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
				<th scope="row"><label for="thing">Thing</label></th>
				<td><input name="thing" type="text" id="thing" value="thing" autocomplete="off" /></td>
			</tr>
		</table>
		<?php submit_button( __('Save Changes', 'gifdrop' ), 'primary', 'submit', true ); ?>
	</form>
</div>
