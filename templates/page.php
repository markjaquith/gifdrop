<!DOCTYPE HTML>
<html>
<head>
	<title><?php _e( 'GifDrop', 'gifdrop' ); ?></title>
	<?php wp_print_scripts( array( 'gifdrop' ) ); ?>
	<?php wp_print_styles( array( 'gifdrop' ) ); ?>
</head>
<body>
	<div class="outer-wrapper">
		<noscript><?php _e( 'JavaScript needs to be enabled.' ); ?></noscript>
	</div>

<?php // Backbone templates ?>

<script type="text/html" id="tmpl-gif">
<img src="{{data.static}}" width="{{data.fitWidth}}" height="{{data.origFitHeight}}" />
</script>

<script type="text/html" id="tmpl-nav">
NAV BAR GOES HERE
</script>
</body>
</html>
