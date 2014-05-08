$ = window.jQuery
app = window.gifDropAdmin =
	views: []

	add: (select) -> select.clone().appendTo app.selections

	init: ->
		app.pages = new app.Pages _.map( app.pageIds, (id) -> id: parseInt( id, 10 ) )
		app.views.pages = new app.PagesView
			collection: app.pages
		app.views.pages.init()

# Page model
class app.Page extends Backbone.Model

# Pages collection
class app.Pages extends Backbone.Collection
	initialize: -> @listenTo @, 'userRemove', @remove

# Main view
class app.PagesView extends wp.Backbone.View
	template: wp.template 'gifdrop-pages'

	initialize: ->
		@listenTo @collection, 'add', @addPage
		@listenTo @collection, 'remove', @selectPrevious

	addPage: (model) -> @views.add '.gifdrop-selections-wrap', new app.PageView model: model

	selectPrevious: (model, collection, options) ->
		prev = collection.at _.max [options.index - 1, 0]
		prev.trigger 'select' if prev

	init: ->
		@setSubviews()
		@render()
		$('.gifdrop-select-pages-section').html @el
		@views.ready()

	setSubviews: ->
		unless @views.length
			@views.set '.gifdrop-add-page', new app.PagesViewAdd
			for model in @collection.models
				@addPage model

# View for the add page portion
class app.PagesViewAdd extends wp.Backbone.View
	template: wp.template 'gifdrop-pages-add'
	events:
		'keydown select': 'keydownSelect'
		'click button': 'clickButton'

	keydownSelect: (e) ->
		if e.keyCode is 13
			e.preventDefault()
			@clickButton()

	handleClickButton: (e) =>
		e.preventDefault()
		@clickButton()

	clickButton: ->
		if @dropdown.val()
			app.pages.add id: parseInt( @dropdown.val(), 10 )
			@dropdown.val ''
		@dropdown.focus()

	ready: -> @dropdown = @$ 'select'

# View for each individual page
class app.PageView extends wp.Backbone.View
	template: wp.template 'gifdrop-page'
	className: 'gifdrop-selection'
	events:
		'click button': 'userRemove'
		'keydown select': 'keydown'

	initialize: ->
		@listenTo @model, 'remove', @remove
		@listenTo @model, 'select', @selectRemoveButton

	selectRemoveButton: -> @removeButton.focus()

	keydown: (e) -> e.preventDefault() if e.keyCode is 13

	userRemove: (e) ->
		e.preventDefault()
		@model.trigger 'userRemove', @model

	ready: ->
		@dropdown = @$ 'select'
		@removeButton = @$ 'button'
		@dropdown.val @model.id

$ ->
	app.init()
