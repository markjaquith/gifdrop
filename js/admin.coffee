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
		$( '.gifdrop-add-page input' ).click (e) ->
			e.preventDefault()
			select = $(@).siblings('select')
			if select.val()
				app.pages.add id: parseInt( select.val(), 10 )

class app.Page extends Backbone.Model

class app.Pages extends Backbone.Collection
	model: app.Page

	initialize: ->
		@listenTo @, 'userRemove', @remove

class app.PagesView extends wp.Backbone.View
	className: 'gifdrop-selections'

	initialize: ->
		@listenTo @collection, 'add', @addView

	addView: (model) ->
		@views.add new app.PageView model: model

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
		'click input': 'userRemove'

	initialize: ->
		@listenTo @model, 'remove', @remove

	userRemove: (e) ->
		e.preventDefault()
		@model.trigger 'userRemove', @model

	ready: ->
		@$('select').val @model.id

$ ->
	app.init()
