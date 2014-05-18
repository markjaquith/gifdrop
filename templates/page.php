<!DOCTYPE HTML>
<html>
<head>
	<title><?php _e( 'GifDrop', 'gifdrop' ); ?></title>
	<?php wp_print_scripts( array( 'gifdrop' ) ); ?>
	<style>
	* {
		-webkit-box-sizing: border-box;
		   -moz-box-sizing: border-box;
		        box-sizing: border-box;
	}
	body {
		margin: 0;
	}
	.outer-wrapper {
		width: 100%;
		height: 100%;
		background: #ddd;
		position: absolute !important;
	}
	.wrapper {
	}
	.gif {
		float: left;
		position: relative;
	}
	.gif > img {
		display: block;
	}
	.nav {
		position: fixed;
		height: 30px;
		top: 0;
		z-index: 1;
		background: #fff;
		width: 100%;
	}
	.gifs {
		margin-top: 30px;
	}
	</style>
</head>
<body>
	<div class="outer-wrapper">
		<noscript><?php _e( 'JavaScript needs to be enabled.' ); ?></noscript>
	</div>

<?php // Backbone templates ?>

<script type="text/html" id="tmpl-gif">
<img src="{{data.static}}" width="{{data.width}}" height="{{data.height}}" />
</script>

<script type="text/html" id="tmpl-nav">
NAV BAR GOES HERE
</script>
</body>
</html>
