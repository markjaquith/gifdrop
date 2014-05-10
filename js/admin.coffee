$ = window.jQuery
app = window.gifDropAdmin =

	init: ->
		@pages = new @Pages _.map @pageIds, (id) => @allPages.get id
		@pagesView = new @PagesView collection: @pages
		@pagesView.init()

# Page model
class app.Page extends Backbone.Model
	initialize: ->
		@set 'title', app.allPages.get(@get 'id').get 'title' unless @get 'title'

# Pages collection
class app.Pages extends Backbone.Collection
	model: app.Page

	initialize: ->
		@listenTo @, 'removeMe', @remove

# Main view
class app.PagesView extends wp.Backbone.View
	template: wp.template 'gifdrop-pages'

	initialize: ->
		@listenTo @collection, 'add', @addPageView
		@listenTo @collection, 'remove', @selectPrevious

	addPageView: (model) -> @views.add '.gifdrop-selections-wrap', new app.PageView model: model

	selectPrevious: (model, collection, options) ->
		if options?.withKeyboard?
			prev = collection.at _.max [options.index - 1, 0]
			if prev
				prev.trigger 'selectRemoveButton'
			else
				collection.trigger 'selectAddNew'

	init: ->
		@setSubviews()
		@render()
		$('.gifdrop-select-pages-section').html @el
		@views.ready()

	setSubviews: ->
		@views.set '.gifdrop-add-page', new app.PagesViewAdd collection: @collection
		@views.unset '.gifdrop-selections-wrap'
		@addPageView model for model in @collection.models

# View for the add page portion
class app.PagesViewAdd extends wp.Backbone.View
	template: wp.template 'gifdrop-pages-add'
	events:
		'keydown select': 'keydownSelect'
		'click button': 'clickButton'

	initialize: ->
		@listenTo @collection, 'add remove', @disableUsedPages
		@listenTo @collection, 'selectAddNew', @selectAddNew

	disableUsedPages: ->
		@dropdownOptions.attr 'disabled', false
		usedPageIds = _.pluck @collection.models, 'id'
		onlyUsed = -> _.contains usedPageIds, parseInt(@value, 10)
		@dropdownOptions.filter(onlyUsed).attr 'disabled', true

	keydownSelect: (e) ->
		if e.which is 13
			e.preventDefault()
			@clickButton()

	handleClickButton: (e) =>
		e.preventDefault()
		@clickButton()

	clickButton: ->
		if @dropdown.val()
			id = parseInt( @dropdown.val(), 10 )
			app.pages.add app.allPages.get id
			@dropdown.val ''
		@dropdown.focus()

	selectAddNew: -> @dropdown.focus()

	ready: ->
		@dropdown = @$ 'select'
		@dropdownOptions = @dropdown.find 'option'
		@disableUsedPages()

# View for each individual page
class app.PageView extends wp.Backbone.View
	template: wp.template 'gifdrop-page'
	className: 'gifdrop-selection'
	events:
		'click button': 'clickRemove'
		'keydown button': 'pressRemove'

	initialize: ->
		@listenTo @model, 'remove', @remove
		@listenTo @model, 'selectRemoveButton', @selectRemoveButton

	selectRemoveButton: -> @removeButton.focus()

	clickRemove: (e) ->
		e.preventDefault()
		@model.trigger 'removeMe', @model

	pressRemove: (e) ->
		if _.contains [13, 32], e.which
			e.preventDefault()
			@model.trigger 'removeMe', @model, withKeyboard: yes

	prepare: ->
		@model.toJSON()

	ready: ->
		@removeButton = @$ 'button'

$ -> app.init()
