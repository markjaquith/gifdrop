<!DOCTYPE HTML>
<html>
<head>
	<title><?php _e( 'GifDrop', 'gifdrop' ); ?></title>
	<?php wp_print_scripts( array( 'gifdrop' ) ); ?>
	<style>
	body {
		margin: 0;
	}
	.wrapper {
		width: 100%;
		height: 100%;
		background: #ddd;
		position: absolute !important;
	}
	</style>
</head>
<body>
	<div class="wrapper">
		<div class="gifs"></div>
		<noscript><?php _e( 'JavaScript needs to be enabled.' ); ?></noscript>
	</div>
	<div class="browser"></div>
<script type="text/html" id="tmpl-gif">
<div class="gif">
<img src="{{data.src}}" />
</div>
</script>
<script type="text/html" id="tmpl-gifs">
<div class="giflist">
</div>
</scripts>
</body>
</html>