<?php defined( 'WPINC' ) or die; ?>

<script type="text/html" id="tmpl-gif">
<img data-original="{{data.static}}" width="{{data.imgWidth}}" height="{{data.imgHeight}}" />
</script>

<script type="text/html" id="tmpl-nav">
<h1><?php echo esc_html( GifDrop_Plugin::get_instance()->get_site_name() ); ?></h1>
<input class="search" type="text" placeholder="<?php _e( 'Search&hellip;', 'gifdrop' ); ?>" />
</script>

<script type="text/html" id="tmpl-empty">
<p><?php _e( 'Drag gifs here to add them to your collection', 'gifdrop' ); ?></p>
</script>

<script type="text/html" id="tmpl-single">
	<a href="#" class="dashicons dashicons-dismiss"></a>
	<div class="modal-content-inner">
		<div data-clipboard-text="{{data.src}}" class="copy-to-clipboard" style="width:{{data.width}}px">
			<p><img src="{{data.src}}" width="{{data.width}}" height="{{data.height}}" /></p>
			<button type="button" class="copy" data-copied-message="<?php esc_attr_e( 'Copied!', 'gifdrop' ); ?>"><?php _e( 'Copy URL', 'gifdrop' ); ?></button>
			<input type="text" class="copy" readonly value="{{data.src}}" />
		</div>
		<p class="details">
			<i class="dashicons dashicons-tag"></i><input <# if ( ! data.canUpload ) { #>readonly<# } #> class="title" type="text" value="{{data.title}}" />
			<span class="label"><?php _e( 'Keywords', 'gifdrop' ); ?></span>
		</p>
	</div>
</script>

<?php do_action( 'gifdrop_backbone_templates' ); ?>
