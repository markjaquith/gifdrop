$ = window.jQuery

app = window.gifdropApp =
	init: ->
		@images = new @Images _.toArray gifdropSettings.attachments
		@view = new @ImagesListView collection: @images
		@view.init()

$ ->
	uploadProgress = (uploader, file) ->
		# alert 'uploadProgress'
		# $bar = $("#" + uploader.settings.drop_element + " .media-progress-bar div")
		# $bar.width file.percent + "%"
		console.log 'uploadProgress'

	uploadStart = (uploader) ->
		# alert 'uploadStart'
		console.log 'uploadStart'

	uploadError = ->
		alert 'error'

	uploadSuccess = (attachment) ->
		console.log attachment
		img = attachment.attributes.sizes.full
		app.images.add
			id: attachment.id
			width: img.width
			height: img.height
			src: img.url

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

class app.Image extends Backbone.Model

class app.Images extends Backbone.Collection
	model: app.Image

class app.ImagesListView extends wp.Backbone.View
	template: wp.template 'gifs'

	initialize: ->
		@listenTo @collection, 'add', @prependView

	prependView: (model, collection, options) ->
		@addView model, at: 0

	addView: (model, options) ->
		@views.add '.giflist', new app.ImageListView(model: model), options
		console.log 'addView'

	addSubviews: ->
		@addView gif for gif in @collection.models

	init: ->
		@addSubviews()
		@render()
		$('.gifs').html @$el
		console.log @el
		@views.ready()

class app.ImageListView extends wp.Backbone.View
	className: 'gif'
	template: wp.template 'gif'

	prepare: -> @model.toJSON()

$ -> app.init()
