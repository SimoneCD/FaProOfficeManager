/*!

 =========================================================
 * Material Dashboard - v2.1.2
 =========================================================

 * Product Page: https://www.creative-tim.com/product/material-dashboard
 * Copyright 2020 Creative Tim (http://www.creative-tim.com)

 * Designed by www.invisionapp.com Coded by www.creative-tim.com

 =========================================================

 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

 */


var breakCards = true;

var searchVisible = 0;
var transparent = true;

var transparentDemo = true;
var fixedTop = false;

var mobile_menu_visible = 0,
    mobile_menu_initialized = false,
    toggle_initialized = false,
    bootstrap_nav_initialized = false;

var seq = 0,
    delays = 80,
    durations = 500;
var seq2 = 0,
    delays2 = 80,
    durations2 = 500;

jQuery(document).ready(function() {

    $sidebar = jQuery('.sidebar');

    md.initSidebarsCheck();

    window_width = jQuery(window).width();

    //  Activate the tooltips
    // jQuery('[rel="tooltip"]').tooltip();
});

jQuery(document).on('click', '.navbar-toggler', function() {
    $toggle = jQuery(this);

    if (mobile_menu_visible == 1) {
        jQuery('html').removeClass('nav-open');

        jQuery('.close-layer').remove();
        setTimeout(function() {
            $toggle.removeClass('toggled');
        }, 400);

        mobile_menu_visible = 0;
    } else {
        setTimeout(function() {
            $toggle.addClass('toggled');
        }, 430);

        var $layer = jQuery('<div class="close-layer"></div>');

        if (jQuery('body').find('.main-panel').length != 0) {
            $layer.appendTo(".main-panel");

        } else if ((jQuery('body').hasClass('off-canvas-sidebar'))) {
            $layer.appendTo(".wrapper-full-page");
        }

        setTimeout(function() {
            $layer.addClass('visible');
        }, 100);

        $layer.click(function() {
            jQuery('html').removeClass('nav-open');
            mobile_menu_visible = 0;

            $layer.removeClass('visible');

            setTimeout(function() {
                $layer.remove();
                $toggle.removeClass('toggled');

            }, 400);
        });

        jQuery('html').addClass('nav-open');
        mobile_menu_visible = 1;

    }

});

// activate collapse right menu when the windows is resized
jQuery(window).resize(function() {
    md.initSidebarsCheck();

    // reset the seq for charts drawing animations
    seq = seq2 = 0;
});

md = {
    misc: {
        navbar_menu_visible: 0,
        active_collapse: true,
        disabled_collapse_init: 0,
    },

    checkSidebarImage: function() {
    },

    showNotification: function(from, align) {

    },

    initSidebarsCheck: function() {
        if (jQuery(window).width() <= 991) {
            if ($sidebar.length != 0) {
                md.initRightMenu();
            }
        }
    },

    checkFullPageBackgroundImage: function() {

    },

    initRightMenu: debounce(function() {
        $sidebar_wrapper = jQuery('.sidebar-wrapper');
        let f = $sidebar_wrapper.find('.navbar-form');
        if (f !== undefined) {
            f.remove();
            $sidebar_wrapper.find('.nav-mobile-menu').remove();
            if (!mobile_menu_initialized) {
                $navbar = jQuery('nav').find('.navbar-collapse').children('.navbar-nav');

                mobile_menu_content = '';

                nav_content = $navbar.html();

                nav_content = '<ul class="nav navbar-nav nav-mobile-menu">' + nav_content + '</ul>';

                navbar_form = jQuery('nav').find('.navbar-form').get(0).outerHTML;

                $sidebar_nav = $sidebar_wrapper.find(' > .nav');

                // insert the navbar form before the sidebar list
                $nav_content = jQuery(nav_content);
                $navbar_form = jQuery(navbar_form);
                $nav_content.insertBefore($sidebar_nav);
                $navbar_form.insertBefore($nav_content);

                jQuery(".sidebar-wrapper .dropdown .dropdown-menu > li > a").click(function(event) {
                    event.stopPropagation();

                });

                mobile_menu_initialized = true;
            } else {
                if (jQuery(window).width() > 991) {
                    // reset all the additions that we made for the sidebar wrapper only if the screen is bigger than 991px
                    $sidebar_wrapper.find('.navbar-form').remove();
                    $sidebar_wrapper.find('.nav-mobile-menu').remove();

                    mobile_menu_initialized = false;
                }
            }
        }

    }, 200),

}

// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.

function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this,
            args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        }, wait);
        if (immediate && !timeout) func.apply(context, args);
    };
};