(function() {
	if(typeof window.$ == 'undefined' || typeof window.$.fn == 'undefined')
		throw new Error("Jolt requires jQuery or Zepto");
	window.Jolt = {
		/**
		 * Capitalize string function
		 */
		capitalize: function(str) {
			return str.charAt(0).toUpperCase() + str.slice(1);
		},
		enabled: true,
		disable: function() {
			Jolt.enabled = false;
			return Jolt.enabled;
		},
		enable: function() {
			Jolt.enabled = true;
			return Jolt.enabled;
		},
		toggle: function() {
			Jolt.enabled = !Jolt.enabled;
			return Jolt.enabled;
		},
		uri: function(href) {
			var a = document.createElement('a');
			a.href = href;
			return a;
		},
		status: function() {
			var status = {};
			var sections = $('.jolt-section');
			for(var i = 0; i < sections.length; i++) {
				var c = sections[i].className.split(' ');
				for(var j in c) {
					if(c[j].indexOf('jolt-section-') === 0) {
						var key = c[j].substr(13);
						var content = $(sections[i]).children('.jolt-content');
						if(content.length) {
							var d = content[0].className.split(' ');
							for(var k in d) {
								if(d[k].indexOf('jolt-content-') === 0) {
									var slug = d[k].substr(13);
									status[key] = slug;
									/**
									 * Break once we found the key and slug
									 */
									break;
								}
							}
						}
						/**
						 * Break once we found the key and slug
						 */
						break;
					}
				}
			}
			return status;
		},
		reload: function() {
			if(!Jolt.enabled)
				return window.location.reload();

			Jolt.load(window.location.href, 'get', null, true);
		},
		load: function(href, method, data, skipState) {
			if(!Jolt.enabled)
				return true;

			/**
			 * If we have null data make it an empty object
			 */
			if(data === null) {
				data = {};
			}

			/**
			 * Don't load if no href defined
			 */
			if(typeof href === 'undefined')
				return false;
			
			/**
			 * Continue if loading from another domain
			 */
			var uri = Jolt.uri(href);
			href = '' + uri.pathname + uri.search;
			if(location.host != uri.host)
				return true;

			/**
			 * Allow Jolt to be restricted to a portal
			 */
			if(Jolt.portal) {
				if(Jolt.portal != uri.pathname.substr(1, Jolt.portal.length))
					return Jolt.frame(href);
			}

			/**
			 * Prepare request
			 */
			var status = Jolt.status();
			if(typeof method === 'string')
				method = method.toLowerCase();

			/**
			 * Handle GET and POST requests
			 * @author Nate Ferrero
			 */
			if(typeof method !== 'string' || method !== 'post') {
				method = 'get';
				if(typeof data == 'object' && data.length)
					href += (href.indexOf('?') > 0 ? '&' : '?') + $.param(data);
				if(typeof data == 'string' && data.length)
					href += (href.indexOf('?') > 0 ? '&' : '?') + data;
				data = {};
			}

			if(typeof data === 'undefined')
				data = {};
			if(typeof data === 'object')
				data['@jolt'] = status;
			if(typeof data === 'string') {
				var jdata = {'@jolt': status};
				data = (data.length ? data + '&' : '') + $.param(jdata);
			}

			if(console) console.log('Jolt ' + href);
			$.post(href, data, Jolt.showGen(href, skipState ? false : true), 'json');
			return false;
		},
		link: function(e) {
			if(!Jolt.enabled)
				return true;
			var el = $(e.currentTarget);
			if(el.attr('jolt') != 'disabled') {
				var normal = Jolt.load(el.attr('href'));
				if(!normal)
					el.append($('<div>').addClass("loading-icon"));
				return normal;
			} else
				return true;
		},
		form: function(e) {
			if(!Jolt.enabled)
				return true;
			var form = $(e.target);
			var id = form.attr('id');
			var loading = $('.' + id + '-jolt-loading');
			if(loading.length)
				loading.append($('<div>').addClass("loading-icon"));
			var data = form.serialize();
			var method = form.attr('method');
			var action = form.attr('action');
			if(!action) action = window.location.href.split('?')[0];
			return Jolt.load(action, method, data);
		},
		frame: function(href) {
			$('.joltOverflow').children('.wrapper').append(
				/**
				 * Add iframe
				 * @author Nate Ferrero
				 */
				$('<iframe>').addClass('content').attr('src', href).load(function() {
					/**
					 * Hide Loading Icon
					 */
					$(".loading-icon").fadeOut(function() {$(this).remove()});
				})
			).end().show();

			/**
			 * Prevent further loading
			 */
			return false;
		},
		showGen: function(href, pushState) {
			return function(data) {
				$(".loading-icon").fadeOut(function() {$(this).remove()});
				if(typeof data !== 'object')
					return Jolt.frame(href);

				/**
				 * Handle messages
				 * @author Nate Ferrero
				 */
				if(data && data.messages) { /* Eventually make sure errors go to the right place */
					var messages = $(/*'.jolt-section-' + data.section + */' .jolt-messages').first();
					messages.show();
					var show = messages.length > 0;
					data.messages.forEach(function(message) {
						if(show) {
							var mdiv = $("<div>").addClass('messages').addClass(message.type)
								.html('<span class="type">'+Jolt.capitalize(message.type)+'</span><span>'+message.message+'</span>');
							messages.append(mdiv);

							setTimeout(function() {
								mdiv.fadeOut(200, function(){mdiv.remove();});
							}, 2000);
						} else
							alert(message.type + ': ' + message.message);
					});
					return;
				}

				/**
				 * Handle redirects
				 * @author Nate Ferrero
				 */
				if(data && data.redirect)
					return Jolt.load(data.redirect);

				/**
				 * Locate section
				 * @author Nate Ferrero
				 */
				var section = $('.jolt-section-' + data.section);
				if(!section.length)
					throw new Error("Jolt section '"+data.section+"' not found");

				/**
				 * Push state
				 * @author Nate Ferrero
				 */
				if(pushState && typeof data === 'object' && data.href)
					window.history.pushState({href: data.href}, '', data.href);

				/**
				 * Present result
				 * @author Nate Ferrero
				 */
				Jolt.matchLinks(data.href);
				section.html(data.html);
				if(window.onJoltUpdate)
					window.onJoltUpdate(data.href);
			}
		},
		init: function() {
			$('body').on('click', 'a', Jolt.link);
			$('body').on('submit', 'form', Jolt.form);
		},
		state: function(e) {
			if(!e.state) {
				if(Jolt.initialHref === null)
					return Jolt.initialHref = window.location.href;
				var href = Jolt.initialHref;
			} else
				var href = e.state.href;

			/**
			 * Show loading icon on match
			 * @author Nate Ferrero
			 */
			$('a[href="'+href.split('?')[0]+'"]')
				.append($('<div>').addClass("loading-icon"));

			Jolt.load(href, 'get', null, true);
		},
		initialHref: null,
		selectedClass: 'active',
		setSelectedClass: function(cls) {
			Jolt.selectedClass = cls;
		},
		rootPath: '',
		setRootPath: function(path) {
			Jolt.rootPath = path;
		},
		matchLinks: function(href) {
			var uri = Jolt.uri(href);
			if(location.host != uri.host)
				return false;
			var path = uri.pathname.split('/');
			path.shift();
			var rpath = Jolt.rootPath.split('/');
			if(rpath[0] == '')
				rpath.shift();
			while(rpath[0] && path[0] && rpath[0] == path[0]) {
				rpath.shift();
				path.shift();
			}
			var mpath = '', paths = {};
			while(path.length) {
				mpath += (mpath == '' ? '' : '/') + path.shift();
				paths[mpath] = true;
			}
			var jsc = $('*[jolt-selected-class]');
			for(var i = 0; i < jsc.length; i++) {
				var j = $(jsc[i]);
				var cls = j.attr('jolt-selected-class');
				j.find('.'+cls).removeClass(cls);
				var items = j.find('a[jolt-match]');
				for(var j = 0; j < items.length; j++) {
					var matches = $(items[j]).attr('jolt-match').split(',');
					for(var k = 0; k < matches.length; k++) {
						if(paths[matches[k]]) {
							$(items[j]).addClass(cls);
						}
					}
				}
			}
		}
	};
	$(Jolt.init);
	window.onpopstate = Jolt.state;
})();