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
				<th scope="row"><label for="gifdrop-pages"><?php _e( 'GifDrop Pages', 'gifdrop' ); ?></label></th>
				<td class="gifdrop-select-pages-section">
					<noscript><p><?php _e( 'You must enable JavaScript.', 'gifdrop' ); ?></p></noscript>
				</td>
			</tr>
		</table>
		<script>gifDropAdmin.pageIds = <?php echo json_encode( $this->get_page_ids() ); ?>;</script>
		<?php submit_button( __('Save Changes', 'gifdrop' ), 'primary', 'submit', true ); ?>
	</form>
</div>
<script type="text/html" id="tmpl-gifdrop-pages">
	<p><?php _e( 'Select the pages on which GifDrop should be active:', 'gifdrop' ); ?></p>
	<div class="gifdrop-selections-wrap"></div>
	<hr />
	<div class="gifdrop-add-page"></div>
</script>
<script type="text/html" id="tmpl-gifdrop-pages-add">
<?php
wp_dropdown_pages( array(
	'name' => 'ignored',
	'show_option_none' => __( '&mdash; Select &mdash;', 'gifdrop' ),
) ); ?> <button type="button" class="button button-secondary"><?php _e( 'add page' ); ?></button>
<input type="hidden" name="gifdrop_js" value="enabled" />
</script>
<script type="text/html" id="tmpl-gifdrop-page">
<?php wp_dropdown_pages( array(
	'name' => 'gifdrop_enabled[]',
)); ?> <button type="button" class="button button-secondary"><?php _e( 'remove', 'gifdrop' ); ?></button>
</script>
