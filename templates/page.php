<!DOCTYPE HTML>
<html>
<head>
	<title><?php _e( 'GifDrop', 'gifdrop' ); ?></title>
	<?php wp_print_scripts( array( 'gifdrop' ) ); ?>
	<?php wp_print_styles( array( 'gifdrop' ) ); ?>
</head>
<body>
	<div id="outer-wrapper">
		<noscript><?php _e( 'JavaScript needs to be enabled.' ); ?></noscript>
	</div>
	<div id="modal"></div>
<?php // Backbone templates ?>

<script type="text/html" id="tmpl-gif">
<img src="{{data.static}}" width="{{data.fitWidth}}" height="{{data.origFitHeight}}" />
</script>

<script type="text/html" id="tmpl-nav">
<h1><?php _e( 'GifDrop', 'gifdrop' ); ?></h1>
<input class="search" type="text" placeholder="<?php _e( 'Search&hellip;', 'gifdrop' ); ?>" />
</script>

<script type="text/html" id="tmpl-single">
	<img src="{{data.src}}" />
</script>
</body>
</html>
