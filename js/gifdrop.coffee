$ = window.jQuery

app = window.gifdropApp = {}

$ ->
	imageFormatUploadProgress = (uploader, file) ->
		alert 'imageFormatUploadProgress'
		#$bar = $("#" + uploader.settings.drop_element + " .media-progress-bar div")
		#$bar.width file.percent + "%"

	imageFormatUploadStart = (uploader) ->
		#$("#" + uploader.settings.drop_element + " .wp-format-media-select").append "<div class=\"media-progress-bar\"><div></div></div>"
		alert 'imageFormatUploadStart'

	imageFormatUploadError = ->
		#$(".media-progress-bar", $(".wp-format-media-holder[data-format=image]")).remove()
		alert 'error'

	imageFormatUploadSuccess = (attachment) ->
		console.log 'attachment', attachment

	imageFormatUploadFilesAdded = (uploader, files) ->
		$.each files, (i, file) ->
			uploader.removeFile file  if i > 0

	uploader = new wp.Uploader
		container: $ '.wrapper'
		browser: $ '.browser'
		dropzone: $ '.dropzone'
		success: imageFormatUploadSuccess
		error: imageFormatUploadError
		id: 1270
		init: ->
			wp.media.model.settings.post.id = gifdropSettings.id
		supports:
			dragdrop: yes
		params:
			post_id: 1270
		plupload:
			runtimes: "html5"
			filters: [
				title: "Image"
				extensions: "jpg,jpeg,gif,png"
			]

	if uploader.supports.dragdrop
		uploader.uploader.bind "BeforeUpload", imageFormatUploadStart
		uploader.uploader.bind "UploadProgress", imageFormatUploadProgress
		uploader.uploader.bind "FilesAdded", imageFormatUploadFilesAdded
	else
		uploader.uploader.destroy()
		uploader = null

	app.images = new app.Images _.toArray gifdropSettings.attachments

class app.Image extends Backbone.Model

class app.Images extends Backbone.Collection
	model: app.Image
