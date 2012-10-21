(function($, undefined) {

    var Debug = (function () {

        var _public      = {},
            _private     = {},
            templates    = {
                toggle   : '<a href="#" id="cdebugToggle">Debug</a>',
                overlay  : '<div id="cdebugOverlay" />'
            },
            overlay      = null,
            panel        = $('#cdebugPanel'),
            contents     = null,
            navigation   = null,
            toggle       = null,
            toggleHeight = null;

        // Show the panel
        _public.panelShow = function() {

            overlay.fadeIn('fast');
            $('body').css('overflow', 'hidden');

            toggle
                .stop()
                .animate({'top': '10px'}, 'fast', function() {
                    toggle
                        .stop()
                        .animate({'top': '30px'}, 'normal');
                })
                .addClass('expanded');

            panel
                .stop()
                .show()
                .animate({
                    'top': 10 + toggleHeight - 1, 
                    'right': '0px', 
                    'width': '820px'
                }, 
                'fast', 
                function() {
                    panel
                        .stop()
                        .animate({
                            'top': 30 + toggleHeight - 1, 
                            'width': '800px'
                        }, 
                        'normal');
                })
                .addClass('expanded');
        };

        // Hide the panel
        _public.panelHide = function() {
            var height = $(window).height() - toggleHeight;

            overlay.fadeOut('fast');
            $('body').css('overflow', 'auto');

            toggle
                .stop()
                .animate({'top': height}, 'fast')
                .removeClass('expanded');

            panel
                .stop()
                .animate({'top': height, 'right':'-750px'}, 'fast', function() {
                    panel.hide();
                })
                .removeClass('expanded');
        };

        // Toggle panel's visibility
        _public.panelToggle = function() {
            if (toggle.hasClass('expanded')) {
                _public.panelHide();
            }
            else {
                _public.panelShow();
            }
        };

        /**
         * Will switcvh content
         * --
         * @param string newPosition
         */
        _private.contentSwitch = function(newPosition) {
            contents.fadeOut(100);
            contents.filter('.'+newPosition).fadeIn(100);

            navigation.find('a').removeClass('selected');
            navigation.find('.cnt_'+newPosition).addClass('selected');
        };

        // Init the cDebug
        _private.init = function() {
            overlay = $(templates.overlay).appendTo('body');
            toggle  = $(templates.toggle).appendTo('body');
            toggleHeight = toggle.outerHeight();
            contents   = panel.find('div.content');
            navigation = panel.find('div.navigation');

            var countWAR = $('#cdebugPanel .content.log .msgType_WAR').length;
            var countERR = $('#cdebugPanel .content.log .msgType_ERR').length;

            if (countERR > 0) {
                toggle.append(' <span class="cdebugToggleTag cdtttError">' + countERR + '</span>');
            }

            if (countWAR > 0) {
                toggle.append(' <span class="cdebugToggleTag cdtttWarning">' + countWAR + '</span>');
            }

            toggle.on('click', _public.panelToggle);
            overlay.on('click', _public.panelHide);
            navigation.find('a').on('click', function(e) {
                var $this = $(this),
                    classes = $this.attr('class').split(' ');

                e.preventDefault();

                if ($this.hasClass('selected')) {
                    return;
                }

                for(var i = 0; i < classes.length; i++) {
                    if (classes[i].substr(0,4) == 'cnt_') {
                        _private.contentSwitch(classes[i].substr(4));
                        return;
                    }
                }
            });
        };

        _private.init();
        return _public;
    })();

    // Register namespace
    window.Avrelia      = window.Avrelia      || {};
    window.Avrelia.Plug = window.Avrelia.Plug || {};
    window.Avrelia.Plug.Debug = Debug;

})(jQuery);
