(function() {
	if(typeof window.$ == 'undefined' || typeof window.$.fn == 'undefined')
		throw new Error("Jolt requires jQuery or Zepto");
	window.Jolt = {
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
		load: function(href) {
			if(!Jolt.enabled)
				return true;
			/**
			 * Don't load if no href defined
			 */
			if(typeof href === 'undefined')
				return false;
			
			/**
			 * Continue if loading from another domain
			 */
			var uri = Jolt.uri(href);
			if(location.host != uri.host)
				return true;
			href = '' + uri.pathname + uri.search;
			if(console) console.log("Loading", href);
			var status = Jolt.status();
			$.post(href, {'@jolt': status}, Jolt.show, 'json');
			return false;
		},
		link: function(e) {
			if(!Jolt.enabled)
				return true;
			var el = $(e.currentTarget);
			if(el.attr('jolt') != 'disabled')
				return Jolt.load(el.attr('href'));
			else
				return true;
		},
		form: function(e) {
			if(!Jolt.enabled)
				return true;
			console.log(e);
			return false;
		},
		show: function(data) {
			var section = $('.jolt-section-' + data.section);
			if(!section.length)
				throw new Error("Jolt section '"+data.section+"' not found");
			window.history.pushState({}, '', data.href);
			section.html(data.html);
		},
		init: function() {
			$('body').on('click', 'a', Jolt.link);
			$('body').on('submit', 'form', Jolt.form);
		},
		state: function(e) {
			console.log("State", e.state);
		}
	};
	$(Jolt.init);
	window.onpopstate = Jolt.state;
})();