(function($2, undefined) {

	var cDebug = {

		templates : {
			toggle  : '<a href="#" id="cdebugToggle">Debug</a>',
			overlay : '<div id="cdebugOverlay" />'
		},
		overlay    : null,
		panel      : $('#cdebugPanel'),
		contents   : null,
		navigation : null,
		toggle     : null,
		toggleHeight : null,

		/**
		 * Show the panel
		 */
		panelShow : function() {

			cDebug.overlay.fadeIn('fast');
			$('body').css('overflow', 'hidden');

			cDebug.toggle
				.stop()
				.animate({'top': '10px',}, 'fast', function() {
					cDebug.toggle
						.stop()
						.animate({'top': '30px'}, 'normal');
				})
				.addClass('expanded');

			cDebug.panel
				.stop()
				.show()
				.animate({'top': 10 + cDebug.toggleHeight - 1, 'right': '0px', 'width': '820px'}, 'fast', function() {
					cDebug.panel
						.stop()
						.animate({'top': 30 + cDebug.toggleHeight - 1, 'width': '800px'}, 'normal');
				})
				.addClass('expanded');
		},

		/**
		 * Hide the panel
		 */
		panelHide : function() {
			var height = $(window).height() - cDebug.toggleHeight;

			cDebug.overlay.fadeOut('fast');
			$('body').css('overflow', 'auto');

			cDebug.toggle
				.stop()
				.animate({'top': height}, 'fast')
				.removeClass('expanded');

			cDebug.panel
				.stop()
				.animate({'top': height, 'right':'-750px'}, 'fast', function() {
					cDebug.panel.hide();
				})
				.removeClass('expanded');
		},

		/**
		 * Toggle panel's visibility
		 */
		panelToggle : function() {
			if (cDebug.toggle.hasClass('expanded')) {
				cDebug.panelHide();
			}
			else {
				cDebug.panelShow();
			}
		},

		/**
		 * Will switcvh content
		 * --
		 * @param	string	newPosition
		 */
		contentSwitch : function(newPosition) {
			this.contents.fadeOut(100);
			this.contents.filter('.'+newPosition).fadeIn(100);

			this.navigation.find('a').removeClass('selected');
			this.navigation.find('.cnt_'+newPosition).addClass('selected');
		},
		//-

		/**
		 * Init the cDebug
		 */
		init : function() {
			this.overlay = $(this.templates.overlay).appendTo('body');
			this.toggle  = $(this.templates.toggle).appendTo('body');
			this.toggleHeight = this.toggle.outerHeight();
			this.contents   = this.panel.find('div.content');
			this.navigation = this.panel.find('div.navigation');

			var countWAR = $('#cdebugPanel .content.log .msgType_WAR').length;
			var countERR = $('#cdebugPanel .content.log .msgType_ERR').length;

			if (countERR > 0) {
				this.toggle.append(' <span class="cdebugToggleTag cdtttError">' + countERR + '</span>');
			}

			if (countWAR > 0) {
				this.toggle.append(' <span class="cdebugToggleTag cdtttWarning">' + countWAR + '</span>');
			}

			this.toggle.on('click', this.panelToggle);
			this.overlay.on('click', this.panelHide);
			this.navigation.find('a').on('click', function(e) {
				var $this = $(this),
					classes = $this.attr('class').split(' ');

				e.preventDefault();

				if ($this.hasClass('selected')) {
					return;
				}

				for(var i = 0; i < classes.length; i++) {
					if (classes[i].substr(0,4) == 'cnt_') {
						cDebug.contentSwitch(classes[i].substr(4));
						return;
					}
				}
			});
		}
	};

	cDebug.init();

	// Make it public
	window.cDebug = cDebug;

})(jQuery);
