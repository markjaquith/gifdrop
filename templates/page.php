<?php defined( 'WPINC' ) or die; ?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php GifDrop_Plugin::get_site_name( true ); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<?php wp_print_scripts( array( 'gifdrop' ) ); ?>
	<?php wp_print_styles( array( 'gifdrop' ) ); ?>
</head>
<body>
	<div id="outer-wrapper">
		<noscript><?php _e( 'JavaScript needs to be enabled.' ); ?></noscript>
	</div>
	<div id="modal"></div>
	<div id="upload-overlay"><i class="dashicons dashicons-update"></i></div>
	<?php include( GifDrop_Plugin::get_instance()->get_path() . 'templates/backbone.php' ); ?>
	<script>gifdropApp.init();</script>
	<?php do_action( 'gifdrop_post_init' ); ?>
</body>
</html>
