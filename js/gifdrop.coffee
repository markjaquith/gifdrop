$ = window.jQuery

app = window.gifdropApp =
	init: ->
		@settings = gifdropSettings
		@$wrapper = $ 'body > #outer-wrapper'
		@$browser = $ 'body > .browser'
		@$modal = $ 'body > #modal'
		@images = new @Images _.toArray @settings.attachments

		# Modal view
		@modalView = new app.ModalView collection: @images
		@modalView.render()
		@$modal.replaceWith @modalView.el
		@modalView.views.ready()

		# Main view
		@view = new @MainView collection: @images
		@view.render()
		@$wrapper.html @view.el
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
			container: @$wrapper
			browser: @$browser
			dropzone: @$wrapper
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

	restrictHeight: (w, h) ->
		if h > 1.5 * w then 1.5 * w else h

	fitTo: (w, h, newWidth) ->
		ratio = h / w
		[newWidth, Math.round(newWidth * ratio)]

	sync: (options) ->
		options = _.defaults options or {},
			context: @
		options.data = _.defaults options.data or {},
			action: 'gifdrop'
			post_id: @settings.id
			_ajax_nonce: @settings.nonce
		wp.ajax.send options

class app.View extends wp.Backbone.View
	render: ->
		result = super
		@postRender?()
		result

class app.BrowserView extends wp.Backbone.View
	className: 'browser'

class app.Image extends Backbone.Model
	initialize: ->
		[width, height] = app.fitTo @get('width'), @get('height'), 320
		@set
			imgWidth: width
			divHeight: app.restrictHeight width, height
			imgHeight: height

	_sync: (data, options) ->
		app.sync
			context: @
			success: options.success
			error: options.error
			data: data

	sync: (method, model, options) ->
		if 'update' is method
			data =
				subaction: method
				model: JSON.stringify model.toJSON()
			@_sync data, options

class app.ImageContainer extends Backbone.Collection
	model: app.Image

class app.Images extends app.ImageContainer
	initialize: (models) ->
		allModels = (new app.Image model for model in models)
		@filtered = new app.ImageContainer allModels
		@listenTo @filtered, 'change', @changeMain

	changeMain: (model) ->
		@get(model.get 'id').set model.toJSON()

	findGifs: (search) ->
		if search.length > 0
			termWords = _.map search.split( /[ _-]/ ), (s) -> s.toLowerCase()
			lastWord = _.last termWords
			results = @filter (model) ->
				titleWords = _.map model.get('title').split( /[ _-]/ ), (s) -> s.toLowerCase()
				termResults = (for termWord in termWords
					((termWord) ->
						regexes = ( new RegExp( suffix + '$' ) for suffix in ['s', 'es', 'ing' ] )
						for word in titleWords
							found = no
							if lastWord is termWord
								found = 0 is word.indexOf lastWord
							unless found
								found = word is termWord
								for regex in regexes
									found ||= word + suffix is termWord
									found ||= word is termWord + suffix
									found ||= word.replace( regex, '' ) is termWord
									found ||= word is termWord.replace( regex, '' )
							return found if found
					)(termWord)
				)
				termResults = _.filter termResults, (r) -> r
				# All input terms had a "hit"
				termResults.length is termWords.length
		else
			results = @models
		@filtered.reset results

class app.MainView extends app.View
	className: 'wrapper'
	initialize: ->
		@views.add new app.ImageNavView collection: @collection
		@views.add new app.ImagesListView collection: @collection
		@views.add new app.BrowserView

class app.ImageNavView extends app.View
	className: 'nav'
	template: wp.template 'nav'
	events:
		'keyup input.search': 'search'
	lastSearch: ''

	search: ->
		@collection.findGifs @$search.val()

	postRender: ->
		@$search = @$ 'input.search'

	ready: ->
		@$search.focus()

class app.ImagesListView extends app.View
	className: 'gifs'
	masonryEnabled: no

	initialize: ->
		@setSubviews()
		@listenTo @collection, 'add', @addNew
		@listenTo @, 'newView', @animateItemIn
		@listenTo @collection.filtered, 'reset', @filterIsotope

	animateItemIn: (model, $item) ->
		position = @collection.filtered.indexOf model
		max = @collection.filtered.length - 1
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

	filterIsotope: (collection, options ) ->
		@$el.isotope
			filter: ->
				_.contains( _.chain( collection.models ).map( (m) -> "gif-#{m.get 'id'}" ).value(), $(@).attr('id') )

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
				columnWidth: 320
				gutter: 0

class app.ImageListView extends app.View
	className: 'gif'
	template: wp.template 'gif'
	events:
		mouseover: 'mouseover'
		mouseout: 'mouseout'
		click: 'click'
	attributes: ->
		id: "gif-#{@model.get 'id'}"

	prepare: -> @model.toJSON()

	mouseover: ->
		@$img.attr src: @model.get 'src'
		@unCrop()

	mouseout: ->
		@$img.attr src: @model.get 'static'
		@restoreCrop()

	click: ->
		view = new app.SingleView model: @model
		app.modalView.views.set view
		@mouseout()
		app.modalView.$el.show()

	unCrop: ->
		if @model.get('imgHeight') isnt @model.get('divHeight')
			# Restrict width to the width that will allow full height to show inside existing box
			ratio = @model.get('imgWidth') / @model.get('imgHeight')
			newWidth = @model.get('divHeight') * ratio
			difference = @model.get('imgWidth') - newWidth
			@$el.css padding: "0 #{difference/2}px"

	restoreCrop: ->
		if @model.get('imgHeight') isnt @model.get('divHeight')
			@$el.css
				padding: 0
				'z-index': 'auto'

	crop: ->
		@$el.css height: "#{@model.get 'divHeight'}px"

	postRender: ->
		@crop()
		@$img = @$ '> img'

	ready: ->
		@views.parent.trigger 'newView', @model, @$el

class app.ModalView extends app.View
	attributes:
		id: 'modal'
	events:
		click: 'click'

	close: ->
		@$el.hide()

	click: (e) ->
		@close() if @el is e.target

class app.SingleView extends app.View
	template: wp.template 'single'
	className: 'modal-content'
	events:
		'click button.save': 'save'
		'keypress input': 'keypress'

	save: ->
		@model.set title: @$title.val()
		@model.save()
		@views.parent.close()

	keypress: (e) ->
		@save() if e.which is 13

	postRender: ->
		@$title = @$ 'input.title'

	prepare: -> @model.toJSON()

$ -> app.init()
