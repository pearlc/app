$(function () {
	'use strict';
	var navigation = $('#WikiHeader').detach(),
		svgChevron = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg"' +
			' x="0px" y="0px" width="5px" height="9px" viewBox="0 0 5 9" ' +
			' enable-background="new 0 0 5 9" xml:space="preserve">' +
			' <polygon points="0.725,0 0,0.763 3.553,4.501 0,8.237 0.725,9 5,4.505 4.994,4.501 5,4.495"/> ' +
			' </svg>';

	$('.WikiaPageHeader').addClass('WikiaPageHeaderV2');
	navigation.find('.buttons').hide();
	navigation.find('.WikiHeaderSearch').hide();
	navigation.removeClass('WikiHeader').addClass('WikiHeaderV2');
	navigation.find('.WikiNav').removeClass('WikiNav').addClass('WikiNavV2');
	navigation.find('.accent').removeClass('accent');
	navigation.find('.marked').removeClass('marked');
	navigation.find('.chevron').remove();
	navigation.find('> nav').unbind();

	navigation.find('.nav-item').each(function () {
		var $this = $(this),
			$seeAll = $this.find('> a').clone(),
			$subNav = $this.find('.subnav-2'),
			$items = $subNav.children(),
			noOfItems = $items.length,
			sizeClass = '',
			seeAllText = (noOfItems > 2) ? ('See all in ' + $seeAll.text()) : 'See all',
			$columns = $(),
			$columnUl,
			columnsCount = 0,
			i = 0;

		window.temp = $subNav;

		$items.find('.subnav-2a').append(svgChevron);
		if (noOfItems === 3) {
			sizeClass = 'submenu-wide';
		} else if (noOfItems > 3) {
			sizeClass = 'submenu-full';
		} else {
			$this.addClass('nav-item-narrow');
		}

		$subNav.wrap('<section class="submenu ' + sizeClass + '"></section>')
			.parent().append($('<div class="clearfix"></div>'));

		if ($seeAll.attr('href') !== '#') {
			$subNav.parent().append($seeAll.html(seeAllText + svgChevron)
				.wrap('<section class="see-all"></section>').parent());
		} else {
			$this.addClass('no-see-all');
		}

		columnsCount = Math.min(4, $items.length);
		for (i = 0; i < columnsCount; i++) {
			$columnUl = $('<ul class="subnav-2-column">');
			$columnUl.append($items.get(i));
			$columnUl.append($items.get(i + 4));

			$columns = $columns.add($('<li>').append($columnUl));
		}
		$subNav.append($columns);
	});

	navigation.children().wrapAll('<section class="local-navigation-container"></section>');
	navigation.insertAfter('#WikiaHeader');

	// COLORS
	(function(){
		/*jshint multistr: true */
		var linkColor = $('.wikia-button').css('background-color') || '#000',
			backgroundColor = $('.WikiaPageBackground').css('background-color') || '#fff',
			css = '.WikiHeaderV2 { \
		background: $header-color-secondary; \
	} \
		.WikiHeaderV2 .wordmark.text a { \
		color: $header-color-primary; \
	} \
 \
	.WikiNavV2 .nav-item > a { \
		background: $header-color-secondary; \
		color: $header-color-primary; \
	} \
	.WikiNavV2 .nav-item > a:hover { \
		background: $header-color-primary; \
		color: $header-color-secondary; \
	} \
	.WikiNavV2 .nav-item:hover > a, .WikiNavV2 .nav-item.active > a { \
		background: $header-color-primary; \
		color: $header-color-secondary; \
	} \
	.WikiNavV2 .nav-item .submenu { \
		background: $header-color-primary; \
		color: $header-color-secondary; \
	} \
	.WikiNavV2 .nav-item .submenu .subnav-2a { \
		border-bottom: 1px solid $header-color-secondary; \
	} \
	.WikiNavV2 .nav-item .submenu .subnav-2a svg polygon { \
		fill: $header-color-secondary; \
	} \
	.WikiNavV2 .nav-item .submenu .see-all { \
		border-top: 1px solid $header-color-secondary; \
	} \
	.WikiNavV2 .nav-item .submenu .see-all svg polygon { \
		fill: $header-color-secondary; \
	} \
	'.replace(/\$header-color-primary/g, linkColor).replace(/\$header-color-secondary/g,
					backgroundColor),
			head = document.head || document.getElementsByTagName('head')[0],
			style = document.createElement('style');

		console.log(css);

		style.type = 'text/css';
		if (style.styleSheet){
			style.styleSheet.cssText = css;
		} else {
			style.appendChild(document.createTextNode(css));
		}

		head.appendChild(style);
	})();
});
