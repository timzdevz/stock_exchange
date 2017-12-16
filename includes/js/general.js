/*-----------------------------------------------------------------------------------*/
/* GENERAL SCRIPTS */
/*-----------------------------------------------------------------------------------*/
jQuery(document).ready(function() {
	// Center navigation dropdowns
	jQuery( '#top').find('.nav > li' ).each( function(){
		var nav_li_width = jQuery(this).width() + 16;
		var nav_ul_width = jQuery(this).children('ul').width();

		var difference = nav_ul_width - nav_li_width;

		if(difference > 0)  {
			// difference  = difference / 2;
			jQuery(this).children('ul').css('margin-left', - difference);
		}

	});

	jQuery( '#top a' ).click( function ( e ) {
		if ( e.target.hash.length && '' != e.target.hash ) {
			jQuery( this ).parents( 'ul' ).find( 'li' ).removeClass( 'current-menu-item' ).removeClass( 'current_page_item' );
			jQuery( this ).parent( 'li' ).addClass( 'current-menu-item' ).addClass( 'current_page_item' );
		}
	});

	// Local Scroll (Source in third-party.js)
	jQuery( '.home #top, .home #wrapper' ).localScroll();

	// Table alt row styling
	jQuery( '.entry table tr:odd' ).addClass( 'alt-table-row' );

	// Add class to parent menu items with JS until WP does this natively
	jQuery( 'ul.sub-menu, ul.children' ).parents( 'li' ).addClass( 'parent' );

	// Stop the navigation link moving to the anchor (Still need the anchor for semantic markup)
	jQuery( '.nav-toggle a' ).click(function(e) {
        e.preventDefault();
      });

  // Display Header
  if ( jQuery( 'body' ).hasClass( 'home' ) ) {
    // Setup "scrollspy"-like functionality.
    var topHeight = jQuery( '#top' ).outerHeight();

    // Cater for the WordPress admin bar, to get an accurate top scroll position.
    if ( jQuery( '#wpadminbar' ).length ) {
    	topHeight += jQuery( '#wpadminbar' ).outerHeight();
    }

    var lastId = 'header';
    var menuItems = jQuery( '#top-nav' ).find( '.hash-item' ).find( 'a[href*="#"]' );
    var scrollItems = menuItems.map(function(){
      var href = jQuery( this ).attr( 'href' );
      var href_split = href.split( '#' );
      var item = jQuery( '#' + href_split[1] );
      if ( item.length ) { return item; }
    });

    jQuery( window ).bind( 'scroll', function ( e ) {
      var fromTop = jQuery(this).scrollTop() + topHeight;

      // Get id of current scroll item
      var cur = scrollItems.map(function(){
        if ( jQuery( this ).offset().top < fromTop )
          return this;
      });
      // Get the id of the current element
      cur = cur[cur.length-1];
      var id = cur && cur.length ? cur[0].id : "";

      if ( '' == id ) id = 'header';

      if ( lastId !== id ) {
        lastId = id;
        // Set/remove active class
        jQuery( '#top-nav' ).find( '.current-menu-item' ).removeClass( 'current-menu-item' ).removeClass( 'current_page_item' );
        jQuery( '#top-nav' ).find( 'a[href$="#' + id + '"]' ).parent().addClass( 'current-menu-item' ).addClass( 'current_page_item' );

        // Special case for the "header" ID. Find links that point to the current URL. Use those as the default.
        if ( 'header' == id ) {
        	var currentURL = window.location.href;
        	var currentURL_split = currentURL.split( '#' ); // Remove # value.
        	var currentURL = currentURL_split[0].split( '?' ); // Remove querystring.
        	var currentURL = currentURL[0];
        	jQuery( '#top-nav' ).find( 'a[href="' + currentURL + '"]' ).parent().addClass( 'current-menu-item' ).addClass( 'current_page_item' );
        }
      }
    });
  }
});