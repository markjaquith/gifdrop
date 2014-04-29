<?php
defined( 'WPINC' ) or die;
?>
<?php /*<div class="updated">
	<p><?php _e( 'Settings updated!', 'gifdrop' ); ?></p>
</div>
*/?>
<script>
(function(){
	if ( window.history && window.history.pushState ) {
		window.history.replaceState( {}, '', window.location.toString().replace( /&updated=true/, '' ) );
	}
})();
</script>
