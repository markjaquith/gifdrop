// Generated by CoffeeScript 1.7.1
(function() {
  var $, app,
    __hasProp = {}.hasOwnProperty,
    __extends = function(child, parent) { for (var key in parent) { if (__hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
    __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };

  $ = window.jQuery;

  app = window.gifdropApp = {
    init: function() {
      this.settings = gifdropSettings;
      this.$wrapper = $('body > #outer-wrapper');
      this.$browser = $('body > .browser');
      this.$modal = $('body > #modal');
      this.images = new this.Images(_.toArray(this.settings.attachments));
      this.modalView = new app.ModalView({
        collection: this.images
      });
      this.modalView.render();
      this.$modal.replaceWith(this.modalView.el);
      this.modalView.views.ready();
      this.view = new this.MainView({
        collection: this.images
      });
      this.view.render();
      this.$wrapper.html(this.view.el);
      this.view.views.ready();
      return this.initUploads();
    },
    initUploads: function() {
      var uploadError, uploadFilesAdded, uploadProgress, uploadStart, uploadSuccess, uploader;
      uploadProgress = function(uploader, file) {
        return console.log('uploadProgress');
      };
      uploadStart = function(uploader) {
        return console.log('uploadStart');
      };
      uploadError = function() {
        return alert('error');
      };
      uploadSuccess = function(attachment) {
        var full, gif, unanimated;
        console.log(attachment);
        full = attachment.attributes.sizes.full;
        unanimated = attachment.attributes.sizes['full-gif-static'] || full;
        gif = {
          id: attachment.id,
          width: full.width,
          height: full.height,
          src: full.url,
          "static": unanimated.url
        };
        return app.images.add(gif, {
          at: 0
        });
      };
      uploadFilesAdded = function(uploader, files) {
        return $.each(files, function(i, file) {
          if (i > 0) {
            return uploader.removeFile(file);
          }
        });
      };
      uploader = new wp.Uploader({
        container: this.$wrapper,
        browser: this.$browser,
        dropzone: this.$wrapper,
        success: uploadSuccess,
        error: uploadError,
        params: {
          post_id: gifdropSettings.id,
          provide_full_gif_static: true
        },
        supports: {
          dragdrop: true
        },
        plupload: {
          runtimes: "html5",
          filters: [
            {
              title: "Image",
              extensions: "jpg,jpeg,gif,png"
            }
          ]
        }
      });
      if (uploader.supports.dragdrop) {
        uploader.uploader.bind("BeforeUpload", uploadStart);
        uploader.uploader.bind("UploadProgress", uploadProgress);
        return uploader.uploader.bind("FilesAdded", uploadFilesAdded);
      } else {
        uploader.uploader.destroy();
        return uploader = null;
      }
    },
    restrictHeight: function(w, h) {
      if (h > 1.5 * w) {
        return 1.5 * w;
      } else {
        return h;
      }
    },
    fitTo: function(w, h, newWidth) {
      var ratio;
      ratio = h / w;
      return [newWidth, Math.round(newWidth * ratio)];
    },
    sync: function(options) {
      options = _.defaults(options || {}, {
        context: this
      });
      options.data = _.defaults(options.data || {}, {
        action: 'gifdrop',
        post_id: this.settings.id,
        _ajax_nonce: this.settings.nonce
      });
      return wp.ajax.send(options);
    }
  };

  app.View = (function(_super) {
    __extends(View, _super);

    function View() {
      return View.__super__.constructor.apply(this, arguments);
    }

    View.prototype.render = function() {
      var result;
      result = View.__super__.render.apply(this, arguments);
      if (typeof this.postRender === "function") {
        this.postRender();
      }
      return result;
    };

    return View;

  })(wp.Backbone.View);

  app.BrowserView = (function(_super) {
    __extends(BrowserView, _super);

    function BrowserView() {
      return BrowserView.__super__.constructor.apply(this, arguments);
    }

    BrowserView.prototype.className = 'browser';

    return BrowserView;

  })(wp.Backbone.View);

  app.Image = (function(_super) {
    __extends(Image, _super);

    function Image() {
      return Image.__super__.constructor.apply(this, arguments);
    }

    Image.prototype.initialize = function() {
      var height, width, _ref;
      _ref = app.fitTo(this.get('width'), this.get('height'), 320), width = _ref[0], height = _ref[1];
      return this.set({
        imgWidth: width,
        divHeight: app.restrictHeight(width, height),
        imgHeight: height
      });
    };

    Image.prototype._sync = function(data, options) {
      return app.sync({
        context: this,
        success: options.success,
        error: options.error,
        data: data
      });
    };

    Image.prototype.sync = function(method, model, options) {
      var data;
      if ('update' === method) {
        data = {
          subaction: method,
          model: JSON.stringify(model.toJSON())
        };
        return this._sync(data, options);
      }
    };

    return Image;

  })(Backbone.Model);

  app.Images = (function(_super) {
    __extends(Images, _super);

    function Images() {
      return Images.__super__.constructor.apply(this, arguments);
    }

    Images.prototype.model = app.Image;

    Images.prototype.initialize = function(models) {
      var allModels, model;
      allModels = (function() {
        var _i, _len, _results;
        _results = [];
        for (_i = 0, _len = models.length; _i < _len; _i++) {
          model = models[_i];
          _results.push(new app.Image(model));
        }
        return _results;
      })();
      this.filtered = new Backbone.Collection(allModels);
      return this.listenTo(this.filtered, 'change', this.changeMain);
    };

    Images.prototype.changeMain = function(model) {
      return this.get(model).set(model.toJSON());
    };

    Images.prototype.findGifs = function(search) {
      var lastWord, results, termWords;
      if (search.length > 0) {
        termWords = _.map(search.split(/[ _-]/), function(s) {
          return s.toLowerCase();
        });
        lastWord = _.last(termWords);
        results = this.filter(function(model) {
          var termResults, termWord, titleWords;
          titleWords = _.map(model.get('title').split(/[ _-]/), function(s) {
            return s.toLowerCase();
          });
          termResults = (function() {
            var _i, _len, _results;
            _results = [];
            for (_i = 0, _len = termWords.length; _i < _len; _i++) {
              termWord = termWords[_i];
              _results.push((function(termWord) {
                var found, regex, regexes, suffix, word, _j, _k, _len1, _len2;
                regexes = (function() {
                  var _j, _len1, _ref, _results1;
                  _ref = ['s', 'es', 'ing'];
                  _results1 = [];
                  for (_j = 0, _len1 = _ref.length; _j < _len1; _j++) {
                    suffix = _ref[_j];
                    _results1.push(new RegExp(suffix + '$'));
                  }
                  return _results1;
                })();
                for (_j = 0, _len1 = titleWords.length; _j < _len1; _j++) {
                  word = titleWords[_j];
                  found = false;
                  if (lastWord === termWord) {
                    found = 0 === word.indexOf(lastWord);
                  }
                  if (!found) {
                    found = word === termWord;
                    for (_k = 0, _len2 = regexes.length; _k < _len2; _k++) {
                      regex = regexes[_k];
                      found || (found = word + suffix === termWord);
                      found || (found = word === termWord + suffix);
                      found || (found = word.replace(regex, '') === termWord);
                      found || (found = word === termWord.replace(regex, ''));
                    }
                  }
                  if (found) {
                    return found;
                  }
                }
              })(termWord));
            }
            return _results;
          })();
          termResults = _.filter(termResults, function(r) {
            return r;
          });
          return termResults.length === termWords.length;
        });
      } else {
        results = this.models;
      }
      return this.filtered.reset(results);
    };

    return Images;

  })(Backbone.Collection);

  app.MainView = (function(_super) {
    __extends(MainView, _super);

    function MainView() {
      return MainView.__super__.constructor.apply(this, arguments);
    }

    MainView.prototype.className = 'wrapper';

    MainView.prototype.initialize = function() {
      this.views.add(new app.ImageNavView({
        collection: this.collection
      }));
      this.views.add(new app.ImagesListView({
        collection: this.collection
      }));
      return this.views.add(new app.BrowserView);
    };

    return MainView;

  })(app.View);

  app.ImageNavView = (function(_super) {
    __extends(ImageNavView, _super);

    function ImageNavView() {
      return ImageNavView.__super__.constructor.apply(this, arguments);
    }

    ImageNavView.prototype.className = 'nav';

    ImageNavView.prototype.template = wp.template('nav');

    ImageNavView.prototype.events = {
      'keyup input.search': 'search'
    };

    ImageNavView.prototype.lastSearch = '';

    ImageNavView.prototype.search = function() {
      return this.collection.findGifs(this.$search.val());
    };

    ImageNavView.prototype.postRender = function() {
      return this.$search = this.$('input.search');
    };

    ImageNavView.prototype.ready = function() {
      return this.$search.focus();
    };

    return ImageNavView;

  })(app.View);

  app.ImagesListView = (function(_super) {
    __extends(ImagesListView, _super);

    function ImagesListView() {
      this.masonry = __bind(this.masonry, this);
      return ImagesListView.__super__.constructor.apply(this, arguments);
    }

    ImagesListView.prototype.className = 'gifs';

    ImagesListView.prototype.masonryEnabled = false;

    ImagesListView.prototype.initialize = function() {
      this.setSubviews();
      this.listenTo(this.collection.filtered, 'add', this.addNew);
      this.listenTo(this, 'newView', this.animateItemIn);
      return this.listenTo(this.collection.filtered, 'reset', this.filterIsotope);
    };

    ImagesListView.prototype.animateItemIn = function(model, $item) {
      var max, position;
      position = this.collection.filtered.indexOf(model);
      max = this.collection.filtered.length - 1;
      if (this.masonryEnabled) {
        switch (position) {
          case 0:
            return this.$el.isotope('prepended', $item);
          case max:
            return this.$el.isotope('appended', $item);
          default:
            return this.$el.isotope('reloadItems').isotope();
        }
      }
    };

    ImagesListView.prototype.addNew = function(model, collection, options) {
      return this.addView(model, {
        at: options != null ? options.at : void 0
      });
    };

    ImagesListView.prototype.addView = function(model, options) {
      var view;
      view = new app.ImageListView({
        model: model
      });
      return this.views.add(view, options);
    };

    ImagesListView.prototype.filterIsotope = function(collection, options) {
      return this.$el.isotope({
        filter: function() {
          return _.contains(_.chain(collection.models).map(function(m) {
            return "gif-" + (m.get('id'));
          }).value(), $(this).attr('id'));
        }
      });
    };

    ImagesListView.prototype.setSubviews = function() {
      var gifViews;
      gifViews = _.map(this.collection.filtered.models, function(gif) {
        return new app.ImageListView({
          model: gif
        });
      });
      return this.views.set(gifViews);
    };

    ImagesListView.prototype.ready = function() {
      return $((function(_this) {
        return function() {
          return _this.masonry();
        };
      })(this));
    };

    ImagesListView.prototype.masonry = function() {
      this.masonryEnabled = true;
      return this.$el.isotope({
        layoutMode: 'masonry',
        itemSelector: '.gif',
        sortBy: 'original-order',
        masonry: {
          columnWidth: 320,
          gutter: 0
        }
      });
    };

    return ImagesListView;

  })(app.View);

  app.ImageListView = (function(_super) {
    __extends(ImageListView, _super);

    function ImageListView() {
      return ImageListView.__super__.constructor.apply(this, arguments);
    }

    ImageListView.prototype.className = 'gif';

    ImageListView.prototype.template = wp.template('gif');

    ImageListView.prototype.events = {
      mouseover: 'mouseover',
      mouseout: 'mouseout',
      click: 'click'
    };

    ImageListView.prototype.attributes = function() {
      return {
        id: "gif-" + (this.model.get('id'))
      };
    };

    ImageListView.prototype.prepare = function() {
      return this.model.toJSON();
    };

    ImageListView.prototype.mouseover = function() {
      this.$img.attr({
        src: this.model.get('src')
      });
      return this.unCrop();
    };

    ImageListView.prototype.mouseout = function() {
      this.$img.attr({
        src: this.model.get('static')
      });
      return this.restoreCrop();
    };

    ImageListView.prototype.click = function() {
      var view;
      view = new app.SingleView({
        model: this.model
      });
      app.modalView.views.set(view);
      this.mouseout();
      return app.modalView.$el.show();
    };

    ImageListView.prototype.unCrop = function() {
      var difference, newWidth, ratio;
      if (this.model.get('imgHeight') !== this.model.get('divHeight')) {
        ratio = this.model.get('imgWidth') / this.model.get('imgHeight');
        newWidth = this.model.get('divHeight') * ratio;
        difference = this.model.get('imgWidth') - newWidth;
        return this.$el.css({
          padding: "0 " + (difference / 2) + "px"
        });
      }
    };

    ImageListView.prototype.restoreCrop = function() {
      if (this.model.get('imgHeight') !== this.model.get('divHeight')) {
        return this.$el.css({
          padding: 0,
          'z-index': 'auto'
        });
      }
    };

    ImageListView.prototype.crop = function() {
      return this.$el.css({
        height: "" + (this.model.get('divHeight')) + "px"
      });
    };

    ImageListView.prototype.postRender = function() {
      this.crop();
      return this.$img = this.$('> img');
    };

    ImageListView.prototype.ready = function() {
      return this.views.parent.trigger('newView', this.model, this.$el);
    };

    return ImageListView;

  })(app.View);

  app.ModalView = (function(_super) {
    __extends(ModalView, _super);

    function ModalView() {
      return ModalView.__super__.constructor.apply(this, arguments);
    }

    ModalView.prototype.attributes = {
      id: 'modal'
    };

    ModalView.prototype.events = {
      click: 'click'
    };

    ModalView.prototype.click = function(e) {
      if (this.el === e.target) {
        return this.$el.hide();
      }
    };

    return ModalView;

  })(app.View);

  app.SingleView = (function(_super) {
    __extends(SingleView, _super);

    function SingleView() {
      return SingleView.__super__.constructor.apply(this, arguments);
    }

    SingleView.prototype.template = wp.template('single');

    SingleView.prototype.className = 'modal-content';

    SingleView.prototype.events = {
      'click button.save': 'save'
    };

    SingleView.prototype.save = function() {
      this.model.set({
        title: this.$title.val()
      });
      return this.model.save();
    };

    SingleView.prototype.postRender = function() {
      return this.$title = this.$('input.title');
    };

    SingleView.prototype.prepare = function() {
      return this.model.toJSON();
    };

    return SingleView;

  })(app.View);

  $(function() {
    return app.init();
  });

}).call(this);


//# sourceMappingURL=gifdrop.map
