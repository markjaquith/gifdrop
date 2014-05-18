$ = window.jQuery

app = window.gifdropApp =
	init: ->
		@images = new @Images _.toArray gifdropSettings.attachments
		@view = new @MainView collection: @images
		@view.render()
		$('.outer-wrapper').html @view.el
		@view.views.ready()
		@initUploads()

	initUploads: ->
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
			gif =
				id: attachment.id
				width: full.width
				height: full.height
				src: full.url
				static: unanimated.url
			app.images.add gif, at: 0

		uploadFilesAdded = (uploader, files) ->
			$.each files, (i, file) ->
				uploader.removeFile file  if i > 0

		uploader = new wp.Uploader
			container: $ '.outer-wrapper'
			browser: $ '.browser'
			dropzone: $ '.outer-wrapper'
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

	ratios: [
		1/4
		1/3
		1/2
		2/3
		3/4
		1
		4/3
		3/2
		2
		3
		4
	]

	restrictRatio: (w, h) ->
		a = Math.abs
		ratio = w/h
		index = _.sortedIndex @ratios, ratio
		before = @ratios[_.max [index - 1, 0]]
		after = @ratios[index]
		newRatio = if a(ratio - before) > a(ratio - after) then after else before
		if newRatio > ratio then [w, w/newRatio] else [h * newRatio, h]

class app.View extends wp.Backbone.View
	render: ->
		result = super
		@postRender?()
		result

class app.BrowserView extends wp.Backbone.View
	className: 'browser'

class app.Image extends Backbone.Model

class app.Images extends Backbone.Collection
	model: app.Image

class app.MainView extends app.View
	className: 'wrapper'
	initialize: ->
		@views.add new app.ImageNavView collection: @collection
		@views.add new app.ImagesListView collection: @collection
		@views.add new app.BrowserView

class app.ImageNavView extends app.View
	className: 'nav'
	template: wp.template 'nav'

class app.ImagesListView extends app.View
	className: 'gifs'
	masonryEnabled: no

	initialize: ->
		@setSubviews()
		@listenTo @collection, 'add', @addNew
		@listenTo @, 'newView', @animateItemIn

	animateItemIn: (model, $item) ->
		position = @collection.indexOf model
		max = @collection.length - 1
		if @masonryEnabled
			switch position
				when 0 then @$el.isotope 'prepended', $item
				when max then @$el.isotope 'appended', $item
				else @$el.isotope('reloadItems').isotope()

	addNew: (model, collection, options) ->
		@addView model, at: options?.at

	addView: (model, options) ->
		view = new app.ImageListView model: model
		@views.add view, options

	setSubviews: ->
		gifViews = _.map @collection.models, (gif) -> new app.ImageListView model: gif
		@views.set gifViews

	ready: -> $ => @masonry()

	masonry: =>
		@masonryEnabled = yes
		@$el.isotope
			layoutMode: 'masonry'
			itemSelector: '.gif'
			sortBy: 'original-order' # This is a "magic" value that respects the DOM
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
		@$img = @$ '> img'

	ready: ->
		@views.parent.trigger 'newView', @model, @$el

$ -> app.init()
