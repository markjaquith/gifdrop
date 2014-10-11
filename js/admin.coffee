$ = window.jQuery
app = window.gifDropAdmin =
	init: ->
		$('#gifdrop-path').keypress (e) ->
			key = e.which
			keyString = String.fromCharCode key
			allowed = /^[a-zA-Z0-9\/-]$/
			# console.log 'Pressed key', key, keyString
			return no unless key is 0 or key is 13 or key is 8 or keyString is '' or keyString.match allowed
$ -> app.init()
