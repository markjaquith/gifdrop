$ = window.jQuery
app = window.gifDropAdmin =
	init: ->
		$('#gifdrop-path').keypress (e) ->
			key = e.which
			console.log key
			# Only allow 0-9, a-z, and - (dash)
			# 13 = return
			# 45 = -
			# 47 = /
			# 97-122 = a-z
			# 48-57 = 0-9
			return no unless key is 45 or key is 47 or key is 13 or 96 < key < 123 or 47 < key < 58

$ -> app.init()
