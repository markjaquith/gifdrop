$ = window.jQuery

app = window.gifdropApp =
	init: ->
		@settings = gifdropSettings
		@settings.canUpload = '1' is @settings.canUpload # Cast to bool
		@$wrapper = $ 'body > #outer-wrapper'
		@$modal = $ 'body > #modal'
		@deviceWidth = if window.innerWidth > 0 then window.innerWidth else screen.width
		@imageWidth = if @deviceWidth >= 640 then 320 else Math.floor( @deviceWidth / 2 )
		$("head").append "<style>.gif { width: #{@imageWidth}px; }</style>"
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

		@$browser = $ '.browser'

		@modalView.listenTo @modalView, 'modalOpen', ->
			$('body').addClass 'modal-open'
		@modalView.listenTo @modalView, 'modalClosed', ->
			$('body').removeClass 'modal-open'
			$('input.search').focus()

		@initUploads()

	initUploads: ->
		uploadProgress = (uploader, file) ->
			# $bar = $("#" + uploader.settings.drop_element + " .media-progress-bar div")
			# $bar.width file.percent + "%"
			# console.log 'uploadProgress', uploader, file

		uploadStart = (uploader) ->
			# console.log 'uploadStart', uploader

		uploadError = ->
			alert 'error'

		uploadSuccess = (attachment) ->
			# console.log attachment
			attr = attachment.attributes
			full = attr.sizes.full
			unanimated = attr.sizes['full-gif-static'] or full
			gif =
				id: attachment.id
				width: full.width
				height: full.height
				title: attr.title
				src: full.url
				static: unanimated.url
			app.images.add gif, at: 0

		uploader = new wp.Uploader
			container: @$wrapper
			browser: @$browser
			dropzone: @$wrapper
			success: uploadSuccess
			error: uploadError
			params:
				post_id: @settings.id
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

	prepare: ->
		@model?.toJSON?()

class app.BrowserView extends wp.Backbone.View
	className: 'browser'

class app.Image extends Backbone.Model
	initialize: ->
		[width, height] = app.fitTo @get('width'), @get('height'), app.imageWidth
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

	_findGifs: (search) ->
		# console.log 'Searching for', search
		if search.length > 2
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
			options = {}
		else
			results = @models
			options = all: yes
		[results, options]

	findGifs: (search) ->
		@memoizedFindGifs ?= _.memoize @_findGifs
		[results, options] = @memoizedFindGifs search
		# console.log 'Search results', results.length
		unless _.isEqual @filtered.pluck('id'), _.pluck( results, 'id' )
			@filtered.reset results, options

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

	_search: (terms) ->
		@collection.findGifs terms

	search: (e) ->
		if e.which is 27
			@$search.val ''
		@debouncedSearch ?= _.debounce @_search, 250
		@debouncedSearch @$search.val()

	postRender: ->
		@$search = @$ 'input.search'

	ready: ->
		@$search.focus()

class app.ImagesListEmptyView extends app.View
	className: 'gifs-empty',
	template: wp.template 'empty'

	initialize: ->
		@listenTo @collection, 'add', @remove

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
		$("body").animate scrollTop: 0, 200
		ids = collection.pluck 'id'
		if options?.all
			filter = -> yes
		else
			filter = ->
				_.contains( _.chain( ids ).map( (id) -> "gif-#{id}" ).value(), $(@).attr('id') )
		view.trigger 'loadImage' for view in @views.get() when _.contains( ids, view.model.get('id') )
		@$el.isotope filter: filter

	setSubviews: ->
		gifViews = _.map @collection.models, (gif) -> new app.ImageListView model: gif
		if gifViews.length
			@views.set gifViews
		else if app.settings.canUpload
			@views.add new app.ImagesListEmptyView collection: @collection

	ready: -> $ => @masonry()

	masonry: =>
		@masonryEnabled = yes
		@$el.isotope
			layoutMode: 'masonry'
			itemSelector: '.gif'
			transitionDuration: '300ms'
			visibleStyle:
				opacity: 1
			hiddenStyle:
				opacity: 0
			sortBy: 'original-order' # This is a "magic" value that respects the DOM
			masonry:
				columnWidth: app.imageWidth
				gutter: 0

class app.ImageListView extends app.View
	className: 'gif'
	template: wp.template 'gif'
	events:
		mouseover: 'mouseover'
		mouseout: 'mouseout'
		click: 'click'

	initialize: ->
		@listenTo @, 'loadImage', @loadImage

	attributes: ->
		id: "gif-#{@model.get 'id'}"

	mouseover: ->
		@$img.attr src: @model.get 'src'
		@unCrop()

	mouseout: ->
		@$img.attr src: @model.get 'static'
		@restoreCrop()

	click: ->
		view = new app.SingleView model: @model
		app.modalView.open()
		app.modalView.views.set view
		@mouseout()

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

	loadImage: ->
		@$img.trigger 'appear'

	postRender: ->
		@crop()
		@$img = @$ '> img'

	ready: ->
		@$img.show().lazyload()
		@views.parent.trigger 'newView', @model, @$el

class app.ModalView extends app.View
	attributes:
		id: 'modal'
	events:
		click: 'click'
		'click a.dashicons-dismiss': 'clickClose'

	keyup: (e) ->
		if e.which is 27
			@close()

	open: ->
		@$el.show().addClass 'open'
		@trigger 'modalOpen'

	close: ->
		@$el.hide().removeClass 'open'
		@trigger 'modalClosed'

	clickClose: (e) ->
		e.preventDefault()
		@close()

	click: (e) ->
		if @el is e.target
			subview.trigger 'modalClosing:click' for subview in @views.get()
			@close()

	ready: ->
		$('body').on 'keyup', (e) =>
			if e.which is 27
				@close()

class app.SingleView extends app.View
	template: wp.template 'single'
	className: 'modal-content'
	events:
		'keyup input.title': 'keyup'
		'click input.copy': 'selectURL'
		'click img': 'selectURL'

	initialize: ->
		@listenTo @, 'modalClosing:click', @save
		@copyButton = null
		@buttonClipboard = null
		@imgClipboard = null

	prepare: ->
		data = super
		data.canUpload = app.settings.canUpload
		data

	save: ->
		@model.set title: @$title.val()
		# console.log 'Saving', @$title.val()
		@model.save()

	keyup: (e) ->
		if e.which is 13
			@save()
			@views.parent.close()
		if e.which is 27
			@$title.val @model.get 'title'
			@views.parent.close()

	resize: =>
		@$contentInner.css height: "#{_.min [$(window).height() - 120, @model.get('height') + 130 ]}px"

	alertCopied: =>
		@$copyButton.html @$copyButton.data 'copied-message'

	selectURL: ->
		if @$copyInput.is ':visible'
			@$copyInput.prop('readonly', no).select().prop('readonly', yes)

	clipboardFallback: =>
		@$copyButton.hide()
		@$copyInput.show()

	postRender: ->
		@$title = @$ 'input.title'
		@$contentInner = @$ '.modal-content-inner'
		@$clipboardWrap = @$contentInner.find '.copy-to-clipboard'
		@$copyButton = @$contentInner.find 'button.copy'
		@$copyInput = @$contentInner.find 'input.copy'

		@$copyButton.css width: @model.get 'width'

		@clipboard = new ZeroClipboard @$clipboardWrap.get(0)
		@clipboard.on 'aftercopy', @alertCopied
		@clipboard.on 'error', @clipboardFallback

		@resize()

	ready: ->
		if app.settings.canUpload
			@$title.focus().val(@$title.val())
		else if @$copyInput.is ':visible'
			@selectURL()
		$(window).on 'resize', _.throttle( @resize, 50 )
