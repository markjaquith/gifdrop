$ = window.jQuery
app = window.gifDropAdmin =

	init: ->
		@pages = new @Pages _.map( @pageIds, (id) -> id: parseInt( id, 10 ) )
		@pagesView = new @PagesView collection: @pages
		@pagesView.init()

# Page model
class app.Page extends Backbone.Model

# Pages collection
class app.Pages extends Backbone.Collection
	initialize: ->
		@listenTo @, 'removeMe', @remove

# Main view
class app.PagesView extends wp.Backbone.View
	template: wp.template 'gifdrop-pages'

	initialize: ->
		@listenTo @collection, 'add', @addPage
		@listenTo @collection, 'remove', @selectPrevious

	addPage: (model) -> @views.add '.gifdrop-selections-wrap', new app.PageView model: model

	selectPrevious: (model, collection, options) ->
		prev = collection.at _.max [options.index - 1, 0]
		prev.trigger 'selectRemoveButton' if prev

	init: ->
		@setSubviews()
		@render()
		$('.gifdrop-select-pages-section').html @el
		@views.ready()

	setSubviews: ->
		@views.set '.gifdrop-add-page', new app.PagesViewAdd
		@views.unset '.gifdrop-selections-wrap'
		@addPage model for model in @collection.models

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

	ready: ->
		@dropdown = @$ 'select'

# View for each individual page
class app.PageView extends wp.Backbone.View
	template: wp.template 'gifdrop-page'
	className: 'gifdrop-selection'
	events:
		'click button': 'clickRemove'

	initialize: ->
		@listenTo @model, 'remove', @remove
		@listenTo @model, 'selectRemoveButton', @selectRemoveButton

	selectRemoveButton: -> @removeButton.focus()

	clickRemove: (e) ->
		e.preventDefault()
		@model.trigger 'removeMe', @model
		@remove()

	ready: ->
		@dropdown = @$ 'select'
		@removeButton = @$ 'button'
		@dropdown.val @model.id

$ -> app.init()
