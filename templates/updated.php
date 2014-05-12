<?php
defined( 'WPINC' ) or die;
?>
<script>
(function(){
	if ( window.history && window.history.pushState ) {
		window.history.replaceState( {}, '', window.location.toString().replace( /&updated=true/, '' ) );
	}
})();
</script>
