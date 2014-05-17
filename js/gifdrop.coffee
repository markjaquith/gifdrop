$ = window.jQuery

app = window.gifdropApp =
	init: ->
		@images = new @Images _.toArray gifdropSettings.attachments
		@view = new @ImagesListView collection: @images
		@view.init()

$ ->
	uploadProgress = (uploader, file) ->
		# $bar = $("#" + uploader.settings.drop_element + " .media-progress-bar div")
		# $bar.width file.percent + "%"
		console.log 'uploadProgress'

	uploadStart = (uploader) ->
		console.log 'uploadStart'

	uploadError = ->
		alert 'error'

	uploadSuccess = (attachment) ->
		console.log attachment
		full = attachment.attributes.sizes.full
		unanimated = attachment.attributes.sizes['full-gif-static'] or full
		app.images.add
			id: attachment.id
			width: full.width
			height: full.height
			src: full.url
			static: unanimated.url

	uploadFilesAdded = (uploader, files) ->
		$.each files, (i, file) ->
			uploader.removeFile file  if i > 0

	uploader = new wp.Uploader
		container: $ '.wrapper'
		browser: $ '.browser'
		dropzone: $ '.wrapper'
		success: uploadSuccess
		error: uploadError
		params:
			post_id: gifdropSettings.id
			provide_full_gif_static: yes
		supports:
			dragdrop: yes
		plupload:
			runtimes: "html5"
			filters: [
				title: "Image"
				extensions: "jpg,jpeg,gif,png"
			]

	if uploader.supports.dragdrop
		uploader.uploader.bind "BeforeUpload", uploadStart
		uploader.uploader.bind "UploadProgress", uploadProgress
		uploader.uploader.bind "FilesAdded", uploadFilesAdded
	else
		uploader.uploader.destroy()
		uploader = null

class app.View extends wp.Backbone.View
	render: ->
		result = super
		@postRender?()
		result

class app.Image extends Backbone.Model

class app.Images extends Backbone.Collection
	model: app.Image

class app.ImagesListView extends app.View
	template: wp.template 'gifs'
	masonryEnabled: no

	initialize: ->
		@listenTo @collection, 'add', @addNew
		@listenTo @, 'prependedView', @prependedView

	prependedView: (item) ->
		@$gifs.isotope 'prepended', item if @masonryEnabled

	addNew: (model, collection, options) ->
		@addView model, at: 0

	addView: (model, options) ->
		@views.add '.giflist', new app.ImageListView(model: model), options

	addSubviews: ->
		@addView gif for gif in @collection.models

	init: ->
		@addSubviews()
		@render()
		$('.gifs').html @$el
		@views.ready()
		@masonry()

	postRender: ->
		@$gifs = @$ '.giflist'

	masonry: ->
		@masonryEnabled = yes
		@$gifs.isotope
			layoutMode: 'masonry'
			itemSelector: '.gif'
			masonry:
				columnWidth: 50

class app.ImageListView extends app.View
	className: 'gif'
	template: wp.template 'gif'
	events:
		'mouseover': 'mouseover'
		'mouseout': 'mouseout'

	prepare: -> @model.toJSON()

	mouseover: -> @$img.attr src: @model.get 'src'

	mouseout: -> @$img.attr src: @model.get 'static'

	postRender: ->
		@$img = @$ 'img'
		@views.parent.trigger 'prependedView', @$el # I don't think this should be here ~ Mark

$ -> app.init()
