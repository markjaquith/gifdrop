<?php defined('WPINC') or die; ?>

<script type="text/html" id="tmpl-gif">
<img src="{{data.static}}" width="{{data.imgWidth}}" height="{{data.imgHeight}}" />
</script>

<script type="text/html" id="tmpl-nav">
<h1><?php _e( 'GifDrop', 'gifdrop' ); ?></h1>
<input class="search" type="text" placeholder="<?php _e( 'Search&hellip;', 'gifdrop' ); ?>" />
</script>

<script type="text/html" id="tmpl-single">
	<p><img src="{{data.src}}" /></p>
	<p class="details">
		<!--<button class="save" type="button"><?php _e( 'Save' ); ?></button>-->
		<input class="title" type="text" value="{{data.title}}" />
		<span class="label"><?php _e( 'Keywords', 'gifdrop' ); ?></span>
	</p>
</script>
