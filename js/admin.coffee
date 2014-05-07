$ = window.jQuery
app = window.gifDropAdmin =
	views: []
	add: (select) ->
		select.clone().appendTo app.selections
	init: ->
		app.pages = new app.Pages _.map( app.pageIds, (id) -> id: parseInt( id, 10 ) )
		app.views.pages = new app.PagesView
			collection: app.pages
		app.views.pages.init()
		app.addSelect = $ '.gifdrop-add-page select'
		app.addButton = $ '.gifdrop-add-page button'
		app.addSelect.keydown (e) ->
			if e.keyCode is 13
				e.preventDefault()
				app.addButton.click()
		app.addButton.click (e) ->
			e.preventDefault()
			$t = $ e.target
			select = $t.siblings 'select'
			if select.val()
				app.pages.add id: parseInt( select.val(), 10 )
				select.val ''
				select.focus()

class app.Page extends Backbone.Model

class app.Pages extends Backbone.Collection
	model: app.Page

	initialize: ->
		@listenTo @, 'userRemove', @remove

class app.PagesView extends wp.Backbone.View
	className: 'gifdrop-selections'

	initialize: ->
		@listenTo @collection, 'add', @addView
		@listenTo @collection, 'remove', @selectPrevious

	addView: (model) ->
		@views.add new app.PageView model: model

	selectPrevious: (model, collection, options) ->
		prev = collection.at _.max [options.index - 1, 0]
		prev.trigger 'select' if prev

	init: ->
		@setSubviews()
		@render()
		$('.gifdrop-selections-wrap').html @el
		@views.ready()

	setSubviews: ->
		unless @views.length
			@views.add new app.PagesViewExtras
			@views.add new app.PageView model: model for model in @collection.models

class app.PagesViewExtras extends wp.Backbone.View
	template: wp.template 'gifdrop-pages-extras'

class app.PageView extends wp.Backbone.View
	template: wp.template 'gifdrop-page'
	className: 'gifdrop-selection'
	events:
		'click button': 'userRemove'
		'keydown select': 'keydown'

	initialize: ->
		@listenTo @model, 'remove', @remove
		@listenTo @model, 'select', @selectRemoveButton

	selectRemoveButton: ->
		@removeButton.focus()

	keydown: (e) ->
		e.preventDefault() if e.keyCode is 13

	userRemove: (e) ->
		e.preventDefault()
		@model.trigger 'userRemove', @model

	ready: ->
		@dropdown = @$ 'select'
		@removeButton = @$ 'button'
		@dropdown.val @model.id

$ ->
	app.init()
