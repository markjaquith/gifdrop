<!DOCTYPE HTML>
<html>
<head>
	<title><?php _e( 'GifDrop', 'gifdrop' ); ?></title>
	<?php wp_print_scripts( array( 'gifdrop' ) ); ?>
	<style>
		.dropzone {
			width: 500px;
			height: 500px;
			background: #ddd;
		}
	</style>
</head>
<body>
	<div class="wrapper">
		<p><?php _e( 'Loading&hellip;', 'gifdrop' ); ?></p>
		<noscript><?php _e( 'JavaScript needs to be enabled.' ); ?></noscript>
	</div>
	<div class="dropzone">DROPZONE</div>
	<div class="browser">BROWSER</div>
</body>
</html>
