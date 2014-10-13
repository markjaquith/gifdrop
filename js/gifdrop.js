(function(){var $,t,e={}.hasOwnProperty,i=function(t,i){function o(){this.constructor=t}for(var n in i)e.call(i,n)&&(t[n]=i[n]);return o.prototype=i.prototype,t.prototype=new o,t.__super__=i.prototype,t},o=function(t,e){return function(){return t.apply(e,arguments)}};$=window.jQuery,t=window.gifdropApp={init:function(){var e,i;return this.settings=gifdropSettings,this.settings.canUpload="1"===this.settings.canUpload,this.$wrapper=$("body > #outer-wrapper"),this.$modal=$("body > #modal"),i=$(window).width(),this.smallMobile=640>i,e=this.smallMobile?2:Math.ceil(i/320),this.imageWidth=Math.floor(i/e),this.setGifWidthCSS(),this.images=new this.Images(_.toArray(this.settings.attachments)),this.modalView=new t.ModalView({collection:this.images}),this.modalView.render(),this.$modal.replaceWith(this.modalView.el),this.modalView.views.ready(),this.view=new this.MainView({collection:this.images}),this.view.render(),this.$wrapper.html(this.view.el),this.view.views.ready(),this.$browser=$(".browser"),this.modalView.listenTo(this.modalView,"modalOpen",function(){return $("body").addClass("modal-open")}),this.modalView.listenTo(this.modalView,"modalClosed",function(){return $("body").removeClass("modal-open"),$("input.search").focus()}),this.initUploads()},setGifWidthCSS:function(){return $("#gifdrop-gif-size").remove(),$("head").append("<style id='gifdrop-gif-size'>.gif { width: "+this.imageWidth+"px; }</style>")},initUploads:function(){var e,i,o,n,r;return i=function(t,e){},o=function(t){return $("body").addClass("uploading")},e=function(){return alert("error")},n=function(e){var i,o,n,r;return $("body").removeClass("uploading"),i=e.attributes,o=i.sizes.full,r=i.sizes["full-gif-static"]||o,n={id:e.id,width:o.width,height:o.height,title:i.title,src:o.url,"static":r.url},t.images.add(n,{at:0})},r=new wp.Uploader({container:this.$wrapper,browser:this.$browser,dropzone:this.$wrapper,success:n,error:e,params:{post_id:this.settings.id,provide_full_gif_static:!0},supports:{dragdrop:!0},plupload:{runtimes:"html5",filters:[{title:"Image",extensions:"jpg,jpeg,gif,png"}]}}),r.supports.dragdrop?(r.uploader.bind("BeforeUpload",o),r.uploader.bind("UploadProgress",i)):(r.uploader.destroy(),r=null)},restrictHeight:function(t,e){return e>1.5*t?1.5*t:e},fitTo:function(t,e,i){var o;return o=e/t,[i,Math.round(i*o)]},sync:function(t){return t=_.defaults(t||{},{context:this}),t.data=_.defaults(t.data||{},{action:"gifdrop",post_id:this.settings.id,_ajax_nonce:this.settings.nonce}),wp.ajax.send(t)}},t.View=function(t){function e(){return e.__super__.constructor.apply(this,arguments)}return i(e,t),e.prototype.render=function(){var t;return t=e.__super__.render.apply(this,arguments),"function"==typeof this.postRender&&this.postRender(),t},e.prototype.prepare=function(){var t;return null!=(t=this.model)&&"function"==typeof t.toJSON?t.toJSON():void 0},e}(wp.Backbone.View),t.BrowserView=function(t){function e(){return e.__super__.constructor.apply(this,arguments)}return i(e,t),e.prototype.className="browser",e}(wp.Backbone.View),t.Image=function(e){function o(){return o.__super__.constructor.apply(this,arguments)}return i(o,e),o.prototype.initialize=function(){var e,i,o;return o=t.fitTo(this.get("width"),this.get("height"),t.imageWidth),i=o[0],e=o[1],this.set({imgWidth:i,divHeight:t.restrictHeight(i,e),imgHeight:e})},o.prototype._sync=function(e,i){return t.sync({context:this,success:i.success,error:i.error,data:e})},o.prototype.sync=function(t,e,i){var o;return"update"===t?(o={subaction:t,model:JSON.stringify(e.toJSON())},this._sync(o,i)):void 0},o}(Backbone.Model),t.ImageContainer=function(e){function o(){return o.__super__.constructor.apply(this,arguments)}return i(o,e),o.prototype.model=t.Image,o}(Backbone.Collection),t.Images=function(e){function o(){return o.__super__.constructor.apply(this,arguments)}return i(o,e),o.prototype.initialize=function(e){var i,o;return i=function(){var i,n,r;for(r=[],i=0,n=e.length;n>i;i++)o=e[i],r.push(new t.Image(o));return r}(),this.filtered=new t.ImageContainer(i),this.listenTo(this.filtered,"change",this.changeMain)},o.prototype.changeMain=function(t){return this.get(t.get("id")).set(t.toJSON())},o.prototype._findGifs=function(t){var e,i,o,n;return t.length>2?(n=_.map(t.split(/[ _-]/),function(t){return t.toLowerCase()}),e=_.last(n),o=this.filter(function(t){var i,o,r;return r=_.map(t.get("title").split(/[ _-]/),function(t){return t.toLowerCase()}),i=function(){var t,i,s;for(s=[],t=0,i=n.length;i>t;t++)o=n[t],s.push(function(t){var i,o,n,s,p,a,l,u,c;for(n=function(){var t,e,i,o;for(i=["s","es","ing"],o=[],t=0,e=i.length;e>t;t++)s=i[t],o.push(new RegExp(s+"$"));return o}(),a=0,u=r.length;u>a;a++){if(p=r[a],i=!1,e===t&&(i=0===p.indexOf(e)),!i)for(i=p===t,l=0,c=n.length;c>l;l++)o=n[l],i||(i=p+s===t),i||(i=p===t+s),i||(i=p.replace(o,"")===t),i||(i=p===t.replace(o,""));if(i)return i}}(o));return s}(),i=_.filter(i,function(t){return t}),i.length===n.length}),i={}):(o=this.models,i={all:!0}),[o,i]},o.prototype.findGifs=function(t){var e,i,o;return null==this.memoizedFindGifs&&(this.memoizedFindGifs=_.memoize(this._findGifs)),o=this.memoizedFindGifs(t),i=o[0],e=o[1],_.isEqual(this.filtered.pluck("id"),_.pluck(i,"id"))?void 0:this.filtered.reset(i,e)},o}(t.ImageContainer),t.MainView=function(e){function o(){return o.__super__.constructor.apply(this,arguments)}return i(o,e),o.prototype.className="wrapper",o.prototype.initialize=function(){return this.views.add(new t.ImageNavView({collection:this.collection})),this.views.add(new t.ImagesListView({collection:this.collection})),this.views.add(new t.BrowserView)},o}(t.View),t.ImageNavView=function(t){function e(){return e.__super__.constructor.apply(this,arguments)}return i(e,t),e.prototype.className="nav",e.prototype.template=wp.template("nav"),e.prototype.events={"keyup input.search":"search"},e.prototype.lastSearch="",e.prototype._search=function(t){return this.collection.findGifs(t)},e.prototype.search=function(t){return 27===t.which&&this.$search.val(""),null==this.debouncedSearch&&(this.debouncedSearch=_.debounce(this._search,500)),this.debouncedSearch(this.$search.val())},e.prototype.postRender=function(){return this.$search=this.$("input.search")},e.prototype.ready=function(){return this.$search.focus()},e}(t.View),t.ImagesListEmptyView=function(t){function e(){return e.__super__.constructor.apply(this,arguments)}return i(e,t),e.prototype.className="gifs-empty",e.prototype.template=wp.template("empty"),e.prototype.initialize=function(){return this.listenTo(this.collection,"add",this.remove)},e}(t.View),t.ImagesListView=function(e){function n(){return this.masonry=o(this.masonry,this),n.__super__.constructor.apply(this,arguments)}return i(n,e),n.prototype.className="gifs",n.prototype.masonryEnabled=!1,n.prototype.initialize=function(){return this.setSubviews(),this.listenTo(this.collection,"add",this.addNew),this.listenTo(this,"newView",this.animateItemIn),this.listenTo(this.collection.filtered,"reset",this.filterIsotope)},n.prototype.animateItemIn=function(t,e){var i,o;if(o=this.collection.filtered.indexOf(t),i=this.collection.filtered.length-1,this.masonryEnabled)switch(o){case 0:return this.$el.isotope("prepended",e);case i:return this.$el.isotope("appended",e);default:return this.$el.isotope("reloadItems").isotope()}},n.prototype.addNew=function(t,e,i){return this.addView(t,{at:null!=i?i.at:void 0})},n.prototype.addView=function(e,i){var o;return o=new t.ImageListView({model:e}),this.views.add(o,i)},n.prototype.filterIsotope=function(t,e){var i,o,n,r,s,p;for($("body").animate({scrollTop:0},200),o=t.pluck("id"),i=(null!=e?e.all:void 0)?function(){return!0}:function(){return _.contains(_.chain(o).map(function(t){return"gif-"+t}).value(),$(this).attr("id"))},p=this.views.get(),r=0,s=p.length;s>r;r++)n=p[r],_.contains(o,n.model.get("id"))&&n.trigger("loadImage");return this.$el.isotope({filter:i})},n.prototype.setSubviews=function(){var e;return e=_.map(this.collection.models,function(e){return new t.ImageListView({model:e})}),e.length?this.views.set(e):t.settings.canUpload?this.views.add(new t.ImagesListEmptyView({collection:this.collection})):void 0},n.prototype.ready=function(){return $(function(t){return function(){return t.masonry()}}(this))},n.prototype.masonry=function(){return this.masonryEnabled=!0,this.$el.isotope({layoutMode:"masonry",itemSelector:".gif",transitionDuration:"300ms",visibleStyle:{opacity:1},hiddenStyle:{opacity:0},sortBy:"original-order",masonry:{columnWidth:t.imageWidth,gutter:0}})},n}(t.View),t.ImageListView=function(e){function o(){return o.__super__.constructor.apply(this,arguments)}return i(o,e),o.prototype.className="gif",o.prototype.template=wp.template("gif"),o.prototype.events={mouseover:"mouseover",mouseout:"mouseout",click:"click"},o.prototype.initialize=function(){return this.listenTo(this,"loadImage",this.loadImage)},o.prototype.attributes=function(){return{id:"gif-"+this.model.get("id")}},o.prototype.mouseover=function(){return this.$img.attr({src:this.model.get("src")}),this.unCrop()},o.prototype.mouseout=function(){return this.$img.attr({src:this.model.get("static")}),this.restoreCrop()},o.prototype.click=function(){var e;return t.smallMobile?window.location.href=this.model.get("src"):(e=new t.SingleView({model:this.model}),t.modalView.open(),t.modalView.views.set(e),this.mouseout())},o.prototype.unCrop=function(){var t,e,i;return this.model.get("imgHeight")!==this.model.get("divHeight")?(i=this.model.get("imgWidth")/this.model.get("imgHeight"),e=this.model.get("divHeight")*i,t=this.model.get("imgWidth")-e,this.$el.css({padding:"0 "+t/2+"px"})):void 0},o.prototype.restoreCrop=function(){return this.model.get("imgHeight")!==this.model.get("divHeight")?this.$el.css({padding:0,"z-index":"auto"}):void 0},o.prototype.crop=function(){return this.$el.css({height:""+this.model.get("divHeight")+"px"})},o.prototype.loadImage=function(){return this.$img.trigger("appear")},o.prototype.postRender=function(){return this.crop(),this.$img=this.$("> img")},o.prototype.ready=function(){return this.$img.show().lazyload(),this.views.parent.trigger("newView",this.model,this.$el)},o}(t.View),t.ModalView=function(t){function e(){return e.__super__.constructor.apply(this,arguments)}return i(e,t),e.prototype.attributes={id:"modal"},e.prototype.events={click:"click","click a.dashicons-dismiss":"clickClose"},e.prototype.keyup=function(t){return 27===t.which?this.close():void 0},e.prototype.open=function(){return this.$el.show().addClass("open"),this.trigger("modalOpen")},e.prototype.close=function(){return this.$el.hide().removeClass("open"),this.trigger("modalClosed")},e.prototype.clickClose=function(t){return t.preventDefault(),this.close()},e.prototype.click=function(t){var e,i,o,n;if(this.el===t.target){for(n=this.views.get(),i=0,o=n.length;o>i;i++)e=n[i],e.trigger("modalClosing:click");return this.close()}},e.prototype.ready=function(){return $("body").on("keyup",function(t){return function(e){return 27===e.which?t.close():void 0}}(this))},e}(t.View),t.SingleView=function(e){function n(){return this.clipboardFallback=o(this.clipboardFallback,this),this.alertCopied=o(this.alertCopied,this),this.resize=o(this.resize,this),n.__super__.constructor.apply(this,arguments)}return i(n,e),n.prototype.template=wp.template("single"),n.prototype.className="modal-content",n.prototype.events={"keyup input.title":"keyup","click input.copy":"selectURL","click img":"selectURL"},n.prototype.initialize=function(){return this.listenTo(this,"modalClosing:click",this.save),this.copyButton=null,this.buttonClipboard=null,this.imgClipboard=null},n.prototype.prepare=function(){var e;return e=n.__super__.prepare.apply(this,arguments),e.canUpload=t.settings.canUpload,e},n.prototype.save=function(){return this.model.set({title:this.$title.val()}),this.model.save()},n.prototype.keyup=function(t){return 13===t.which&&(this.save(),this.views.parent.close()),27===t.which?(this.$title.val(this.model.get("title")),this.views.parent.close()):void 0},n.prototype.resize=function(){return this.$contentInner.css({height:""+_.min([$(window).height()-120,this.model.get("height")+130])+"px"})},n.prototype.alertCopied=function(){return this.$copyButton.html(this.$copyButton.data("copied-message"))},n.prototype.selectURL=function(){return this.$copyInput.is(":visible")?this.$copyInput.prop("readonly",!1).select().prop("readonly",!0):void 0},n.prototype.clipboardFallback=function(){return this.$copyButton.hide(),this.$copyInput.show()},n.prototype.postRender=function(){return this.$title=this.$("input.title"),this.$contentInner=this.$(".modal-content-inner"),this.$clipboardWrap=this.$contentInner.find(".copy-to-clipboard"),this.$copyButton=this.$contentInner.find("button.copy"),this.$copyInput=this.$contentInner.find("input.copy"),this.$copyButton.css({width:this.model.get("width")}),this.clipboard=new ZeroClipboard(this.$clipboardWrap.get(0)),this.clipboard.on("aftercopy",this.alertCopied),this.clipboard.on("error",this.clipboardFallback),this.resize()},n.prototype.ready=function(){return t.settings.canUpload?this.$title.focus().val(this.$title.val()):this.$copyInput.is(":visible")&&this.selectURL(),$(window).on("resize",_.throttle(this.resize,50))},n}(t.View)}).call(this);