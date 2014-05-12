$ = window.jQuery

app = window.gifdropApp = {}

$ ->
	uploadProgress = (uploader, file) ->
		# alert 'uploadProgress'
		# $bar = $("#" + uploader.settings.drop_element + " .media-progress-bar div")
		# $bar.width file.percent + "%"
		console.log 'uploadProgress'

	uploadStart = (uploader) ->
		# alert 'uploadStart'
		console.log 'uploadStart'

	uploadError = ->
		alert 'error'

	uploadSuccess = (attachment) ->
		console.log attachment
		img = attachment.attributes.sizes.full
		app.images.add
			id: attachment.id
			width: img.width
			height: img.height
			url: img.url

	uploadFilesAdded = (uploader, files) ->
		$.each files, (i, file) ->
			uploader.removeFile file  if i > 0

	uploader = new wp.Uploader
		container: $ '.wrapper'
		browser: $ '.browser'
		dropzone: $ '.dropzone'
		success: uploadSuccess
		error: uploadError
		params:
			post_id: gifdropSettings.id
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

	app.images = new app.Images _.toArray gifdropSettings.attachments

class app.Image extends Backbone.Model

class app.Images extends Backbone.Collection
	model: app.Image
