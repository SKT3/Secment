;(function($, window, document, undefined) {
	"use strict";

	var $win = $(window);
	var $doc = $(document);
	var globalKeys = {
		'esc': 27
	};
	
	window.Helper = window.Helper || {};
	window.Component = window.Component || {};


	/* ------------------------------------------------------------ *\
		# Helpers
	\* ------------------------------------------------------------ */

	/**
	 * Returns a function, that, as long as it continues to be invoked, will not be triggered. The function will be called after it stops being called for N milliseconds. If `immediate` is passed, trigger the function on the leading edge, instead of the trailing.
	 * 
	 * @param  {function}
	 * @param  {int}
	 * @param  {bool}
	 * @return {function}
	 */
	Helper.debounce = function(func, wait, immediate) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	};

	/**
	 * Check if event's target matches given selectors.
	 *
	 * Example:
	 * elementMatch(event, '.accordion-item-head, .accordion-item-body, .accordion-item');
	 * 
	 * @param  {event}
	 * @param  {string|Object}
	 * @return {bool}
	 */
	Helper.elementMatch = function(e, selector) {
		// stop execution when parameters are not valid
		if (typeof e === 'undefined' || !$(selector).length) {
			console.error('Helper.elementMatch: Invalid parameters!');
			return;
		}

		var $target = $(e.target);

		if ($target.closest(selector).length) {
			return true;
		} else {
			return false;
		}
	};

	/**
	 * Get url params.
	 * 
	 * @param  {string}
	 * @param  {string}
	 * @return {string}
	 */
	Helper.getParameterByName = function(name, url) {
		if (!url) url = window.location.href;
		name = name.replace(/[\[\]]/g, "\\$&");
		var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)");
		var results = regex.exec(url);
		if (!results) return null;
		if (!results[2]) return '';
		return decodeURIComponent(results[2].replace(/\+/g, " "));
	};

	Helper.getJsonFromParameters = function(string) {
		var result = {};
		string.split("&").forEach(function(part) {
			var item = part.split("=");
			result[item[0]] = decodeURIComponent(item[1]);
		});
		return result;
	};

	/**
	 * Ajax related helper methods.
	 * 
	 * @type {object}
	 */
	Helper.ajax = {
		sendForm: function(form, callback) {
			var self = this;
			var $form = $(form);
			var action = $form.attr('action');
			var method = $form.attr('method');
			var params = $form.serialize();

			if (typeof action === 'undefined' || typeof method === 'undefined') {
				console.error('Helper.ajax.send: Invalid "action" or "method" attributes on form!');
				return;
			}

			if (typeof callback !== 'function') {
				console.error('Helper.ajax.send: Invalid callback function!');
				return;
			}

			$.ajax({
				url: action,
				type: method,
				dataType: 'json',
				data: params,
				success: function(response) {
					callback(response);
				},
				error: function(jqXHR, exception) {
					self.errors(jqXHR, exception);
				}
			});
		},
		sendParams: function(url, params, callback) {
			var self = this;

			$.ajax({
				url: url,
				type: 'POST',
				dataType: 'json',
				data: params,
				success: function(response) {
					callback(response);
				},
				error: function(jqXHR, exception) {
					self.errors(jqXHR, exception);
				}
			});
		},
		validateForm: function(form) {
			var self = this;
			var $form = $(form);

			// remove error classes
			function errorsClean() {
				$form.find('.field.error').removeClass('error');
				$form.find('.choose.error').removeClass('error');
				$form.find('.form-msg.error').remove();
			}

			// add error classes for different form element types
			function errorsShow(response) {
				for (var key in response.errors) {
					var $element = $(form[key]);
					var type = $element.attr('type');
					var $holder;
					
					if (type === 'checkbox' || type === 'radio') {
						$holder = $element.closest('.choose');
					} else {
						$holder = $element.closest('.field');

						if (!$holder.find('.form-msg.error').length) {
							$('<p class="form-msg error">' + response.errors[key] + '</p>').insertAfter($holder);
						}
					}

					if (!$holder.hasClass('error')) {
						$holder.addClass('error');
					}
				}
			}

			// handle ajax response
			function render(response) {
				if(response.success) {
					// show notification
					if ('msg' in response) {
						toastr.success(response.msg);
					}

					if ('reload' in response) {
						window.location = window.location;
					} else if ('redirectTo' in response) {
						window.location = response.redirectTo;
					}

					errorsClean();
				} else {
					// show notification
					if ('msg' in response) {
						toastr.error(response.msg);
					}

					errorsClean();
					
					errorsShow(response);
				}
			}

			// send form for validation
			$.ajax({
				url: $form.attr('action'),
				method: $form.attr('method'),
				data: $form.serialize(),
				dataType: 'json',
				success: function(response) {
					render(response);
				},
				error: function(jqXHR, exception) {
					self.errors(jqXHR, exception);
				}
			});
		},
		errors: function(jqXHR, exception) {
			if (jqXHR.status === 0) {
				console.error('Not connect.\n Verify Network.');
			} else if (jqXHR.status == 404) {
				console.error('Requested page not found. [404]');
			} else if (jqXHR.status == 500) {
				console.error('Internal Server Error [500].');
			} else if (exception === 'parsererror') {
				console.error('Requested JSON parse failed.');
			} else if (exception === 'timeout') {
				console.error('Time out error.');
			} else if (exception === 'abort') {
				console.error('Ajax request aborted.');
			} else {
				console.error('Uncaught Error.\n' + jqXHR.responseText);
			}
		}
	};

	/**
	 * Link two elements and toggle class on them. For multiple triggers require scope element and toggle the class on it.
	 * 
	 * @param {object}
	 *
	 * TODO:
	 * - refactor custom events and toggle function
	 */
	Helper.Linker = function(options) {
		this.initialize(options);
	};

	Helper.Linker.prototype = {
		defaults: {
			$trigger: null,
			$target: null,
			$scope: null,
			classActive: 'has-flag',
			closeSibblings: false,
			hideable: false
		},
		options: {},
		multipleTriggers: false,
		initialize: function(options) {
			var self = this;
			
			self.options = $.extend(true, {}, self.defaults, options);

			self.validateInput();
		},
		validateInput: function() {
			var self = this;

			if (self.options.$trigger === null || self.options.$target === null) {
				console.error('Helper.Linker: Elements are missing or configuration is not valid!');
				console.error('Helper.Linker: Stop execution!');
				return;
			}

			if (!self.options.$trigger.length || !self.options.$target.length) {
				console.error('Helper.Linker: Elements are missing!');
				console.error('Helper.Linker: Stop execution!');
				return;
			}

			if (self.options.$trigger.length > 1) {
				self.multipleTriggers = true;

				if (self.options.$scope === null || !self.options.$scope.length) {
					console.warn('Helper.Linker: Multiple triggers detected! "$scope" requred!');
					console.error('Helper.Linker: Stop execution!');
					return;
				}
			}

			self.options.$trigger.data('linker', self);

			self.bindEvents();
		},
		bindEvents: function() {
			var self = this;
			var $doc = $(document);

			self.options.$trigger.on('click', function() {
				self.toggle($(this));
			});

			if (self.options.hideable) {
				$doc.on('key:esc', function() {
					self.closeAll();
				});

				try {
					$doc.on('click', function(e) {
						if (!Helper.elementMatch(e, self.options.$trigger) && !Helper.elementMatch(e, self.options.$target)) {
							self.closeAll();
						}
					});
				}
				catch(e) {
					console.error('Helper.Linker: Requre "Helper.elementMatch"!');
				}
			}
		},
		toggle: function($current) {
			var self = this;
			var classActive = self.options.classActive;
			var $currentScope = $current.closest(self.options.$scope);

			if (self.multipleTriggers) {
				if (!$currentScope.hasClass(classActive)) {
					if (self.options.closeSibblings) {
						self.options.$scope.removeClass(classActive);
					}

					$currentScope.addClass(classActive);
					$currentScope.trigger('linker:show');
				} else {
					$currentScope.removeClass(classActive);
					$currentScope.trigger('linker:hide');
				}
			} else {
				if (!$current.hasClass(classActive)) {
					self.options.$trigger.addClass(classActive);
					self.options.$target.addClass(classActive);
					
					self.options.$trigger.trigger('linker:show');
				} else {
					self.options.$trigger.removeClass(classActive);
					self.options.$target.removeClass(classActive);

					self.options.$trigger.trigger('linker:hide');
				}
			}
		},
		closeAll: function() {
			var self = this;

			if (self.multipleTriggers) {
				self.options.$scope.removeClass(self.options.classActive);
				
				self.options.$scope.trigger('linker:hide');
			} else {
				self.options.$trigger.removeClass(self.options.classActive);
				self.options.$target.removeClass(self.options.classActive);
				
				self.options.$trigger.trigger('linker:hide');
			}
		}
	};

	/**
	 * Scroll to certain element.
	 * 
	 * @param {object}
	 */
	Helper.ScrollTo = function(options) {
		this.initialize(options);
	};

	Helper.ScrollTo.prototype = {
		defaults: {
			$trigger: null,
			$target: null,
			thresholdOffset: 0,
			scrollDuration: 1000
		},
		options: {},
		initialize: function(options) {
			var self = this;
			
			self.options = $.extend(true, {}, self.defaults, options);

			self.validateInput();
		},
		validateInput: function() {
			var self = this;

			if (self.options.$trigger.length !== 1 || self.options.$target.length !== 1) {
				console.error('Helper.ScrollTo: Elements are missing or configuration is not valid!');
				console.error('Helper.ScrollTo: Stop execution!');
				return;
			}

			self.options.$trigger.data('scrollto', self);

			self.bindEvents();
		},
		bindEvents: function() {
			var self = this;

			self.options.$trigger.on('click', function(e) {
				e.preventDefault();

				self.scrollTo();
			});
		},
		scrollTo: function($current) {
			var self = this;
		
			$('html, body').stop(true, true).animate({
				scrollTop: self.options.$target.offset().top - self.options.thresholdOffset
			}, self.options.scrollDuration);
		}
	};


	/* ------------------------------------------------------------ *\
		# Components
	\* ------------------------------------------------------------ */
	
	/**
	 * Create Google map with panTo function.
	 * 
	 * https://developers.google.com/maps/documentation/javascript/tutorial
	 *
	 * TODO:
	 * - refactor functions
	 * - advanced validation
	 */
	Component.GoogleMap = (function() {
		var $mapHolder = null;
		var map = null;
		var locations = [];
		var pin = {
			src: '',
			x: 40,
			y: 40
		};
		var tooltips = false;
		var config = {};

		function _init(configuration) {
			config = $.extend(true, {}, config, configuration);

			_validateInput();
		}

		function _validateInput() {
			if (typeof google !== 'object') {
				console.error('Component.GoogleMap: Please link Google Maps API!');
				console.error('Component.GoogleMap: Stop execution!');
				return false;
			}

			if (!config.$mapHolder.length) {
				console.error('Component.GoogleMap: Map element missing!');
				console.error('Component.GoogleMap: Stop execution!');
				return false;
			}

			if (!config.locations.length) {
				console.error('Component.GoogleMap: Require array with all the locations!');
				console.error('Component.GoogleMap: Stop execution!');
				return false;
			}

			_cacheConfig();
		}

		function _cacheConfig() {
			$mapHolder = config.$mapHolder;
			locations = config.locations;
			
			if (typeof config.pin !== 'undefined') {
				pin = config.pin;
			}

			if (typeof config.tooltips !== 'undefined') {
				tooltips = config.tooltips;
			}

			_createMap();
		}
		
		function _createMap() {
			// var mapStyles = [{"featureType":"all","stylers":[{"saturation":0},{"hue":"#e7ecf0"}]},{"featureType":"road","stylers":[{"saturation":-70}]},{"featureType":"transit","stylers":[{"visibility":"off"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"water","stylers":[{"visibility":"simplified"},{"saturation":-60}]}];
			var pinSize = new google.maps.Size(pin.x, pin.y);
			var mapSettings = {
				zoom: 17, // work only when there is one pin on the map
				center: new google.maps.LatLng(locations[0][0], locations[0][1]),
				scrollwheel: false,
				// styles: mapStyles,
				disableDefaultUI: false,
				panControl: true,
				zoomControl: true,
				mapTypeControl: true,
				scaleControl: true,
				streetViewControl: true,
				overviewMapControl: true,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			var bounds = new google.maps.LatLngBounds();
			var infoWindow;
			var i;


			if (tooltips) {
				infoWindow = new google.maps.InfoWindow();

				// check for html in locations array
				try {
					if (locations[0][2].length) {}
				} catch(e) {
					console.warn('Component.GoogleMap: Requre html from locations array!');

					tooltips = false;
				}
			}

			// Display a map on the page.
			map = new google.maps.Map(document.getElementById($mapHolder[0].id), mapSettings);

			function createMarker(location) {
				var latLng = new google.maps.LatLng(location[0], location[1]);
				var marker = new google.maps.Marker({
					position: latLng,
					map: map,
					animation: google.maps.Animation.DROP,
					icon: {
						url: pin.src,
						scaledSize: pinSize,
						// pin point position
						anchor: new google.maps.Point(pin.x / 2, pin.y)
					}
				});

				bounds.extend(latLng);

				if (tooltips) {
					google.maps.event.addListener(marker, 'click', function() {
						infoWindow.close(); // close previously opened infowindow
						infoWindow.setContent('<div class="map-tooltip">' + location[2] + '</div>');
						infoWindow.open(map, marker);
					});
				}
			}

			// Create markers for all locations.
			for (i = 0; i < locations.length; i++) {
				createMarker(locations[i]);
			}

			// Automatically center the map fitting all markers on the screen. Ignore zoom from settings.
			if (locations.length > 1) {
				map.fitBounds(bounds);
			}
		}

		function _panTo(lat, lng) {
			// Override our map zoom level once our fitBounds function runs (Make sure it only runs once)
			// var boundsListener = google.maps.event.addListener(map, 'bounds_changed', function(event) {
			// 	this.setZoom(15);
			// 	google.maps.event.removeListener(boundsListener);
			// });

			map.panTo({
				lat: Number(lat),
				lng: Number(lng)
			});
		}

		return {
			init: function(configuration) {
				_init(configuration);
			},
			panTo: function(lat, lng) {
				_panTo(lat, lng);
			}
		};
	})();


	/* ------------------------------------------------------------ *\
		# Events
	\* ------------------------------------------------------------ */

	$doc.on('keyup', function(e) {
		if (e.keyCode == globalKeys.esc) {
			$doc.trigger('key:esc');
		}
	});

	$doc.ready(function() {

		// scroll elements
		$('.js-scroll').each(function() {
			new Helper.ScrollTo({
				$trigger: $(this),
				$target: $($(this).data('scroll-target')),
				thresholdOffset: 60
			});
		});

		// sliders
		$('.boxes-services-preview').slick({
			autoplay: false,
			swipeToSlide: true,
			arrows: true,
			dots: false,
			infinite: false,
			slidesToShow: 4,
			slidesToScroll: 1,
			responsive: [{
				breakpoint: 1200,
				settings: {
					slidesToShow: 3
				}
			}, {
				breakpoint: 768,
				settings: {
					slidesToShow: 2
				}
			}, {
				breakpoint: 480,
				settings: {
					slidesToShow: 1
				}
			}]
		});

		new Helper.Linker({
			$trigger: $('#js-menu-trigger'),
			$target: $('#js-menu-target'),
			hideable: true
		});

		/**
		 * Inline external svg sprite to all pages
		 *
		 * plugin: svg4everybody
		 * https://github.com/jonathantneal/svg4everybody
		 */
		svg4everybody();

		/**
		 * All notifications configuration
		 *
		 * plugin: toastr
		 * https://github.com/CodeSeven/toastr
		 */
		toastr.options = {
			"closeButton": true,
			"debug": false,
			"newestOnTop": false,
			"progressBar": true,
			"positionClass": "toast-top-full-width",
			"preventDuplicates": false,
			"onclick": null,
			"showDuration": "300",
			"hideDuration": "1000",
			"timeOut": "10000",
			"extendedTimeOut": "1000",
			"showEasing": "swing",
			"hideEasing": "linear",
			"showMethod": "fadeIn",
			"hideMethod": "fadeOut"
		};
	});
})(jQuery, window, document);