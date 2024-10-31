
var $ = jQuery.noConflict();
var docHeight = 1080;

var slider = {
	wrapper: '#pd-slider-wrapper',
	ratio: 'sixteen-nine',
	speed: 500,
	updateData: function(activeSlide) {

		// Update active slide
		$('#pd-slides-truck').children('.pd-slide').removeClass('active prev next');
		activeSlide.addClass('active');
		activeSlide.prev().addClass('prev');
		activeSlide.next().addClass('next');

		// Update nav buttons
		if($('.pd-slide:first-child').hasClass('active')) $('.pd-nav.prev').removeClass('active');
		else $('.pd-nav.prev').addClass('active');
		if($('.pd-slide:last-child').hasClass('active')) $('.pd-nav.next').removeClass('active');
		else $('.pd-nav.next').addClass('active');

		// Update slider info
		$('[itemprop="slide-title"]').text(activeSlide.data('slide-title'));
		$('[itemprop="slide-number"]').text(activeSlide.data('slide-number'));

	},
	slide: {
		preview: function() {

			if($(slider.wrapper).hasClass('pd-slide-preview')) {

				// Thumb resize by ratio
				$('#pd-slide-thumb').addClass(slider.ratio);

				// Fire elastic setup
				pressentation.elasticSetup();

				// Check if content exceed slide size
				var slide = $('.pd-slide:not(.dummy)');
				var wrapper = parseInt($('.pd-slide-wrapper', slide).innerHeight());
				var content = parseInt($('.pd-slide-content', slide).innerHeight());

				// Show warning if content exceed slide
				if(content > wrapper) $('#pd-warning').addClass('show');

				// Template setup
				$('html, body').css({'overflow':'hidden'});
				$('#pd-slider').css('opacity', 1);
			}
		}
	}
}

////////////////////////////////////////////////////////// Size by ratio

var pressentation = {
	widthByRatio: function(ratio, height) {
		switch(ratio) {
			case 'sixteen-nine': var width = (height * 16) / 9; break;
			case 'four-by-three': var width = (height * 4) / 3; break;
			case 'square': var width = height; break;
			default: var width = (height * 16) / 9;
		}
		return width;
	},
	heightByRatio: function(ratio, width) {
		switch(ratio) {
			case 'sixteen-nine': var height = (width * 9) / 16; break;
			case 'four-by-three': var width = (width * 3) / 4; break;
			case 'square': var height = width; break;
			default: var height = (width * 9) / 16;
		}
		return height;
	},
	open: function(presentation) {
		var termID = presentation.data('id');
		var loader = presentation.prev('.pd-loader');
		if(termID) {
			loader.show();
			// Ajax call
			wp.ajax.post('getSlider', {termID: termID})
			.done(function(data) {
				loader.hide();
				$(slider.wrapper).html(data).addClass('show').fadeIn();
				pressentation.elasticSetup();

				// Add classes to slides
				$('#pd-slides-truck .pd-slide:first-child').addClass('active');
				$('#pd-slides-truck .pd-slide:nth-child(2)').addClass('next');

				// Remove scrollbar
				$('html, body').css({'overflow':'hidden'});
			})
			.fail(function(data) {
				// console.log('error');
			});
		}
	},
	close: function() {
		if(!$(slider.wrapper).hasClass('pd-slide-preview')) {
			$(slider.wrapper).removeClass('show').fadeOut();
			$('html, body').css({'overflow':''});
		}
	},
	prev: function(presentation) {
		if($('.pd-slide.active').prev().length) {
			if(presentation.hasClass('all')) {
				var activeSlide = $('.pd-slide:first-child');
				var marginLeft  = $('#pd-slider').innerWidth() * $('.pd-slide.active').prevAll().length;
			} else {
				var activeSlide = $('.pd-slide.active').prev();
				var marginLeft  = $('#pd-slider').innerWidth();
			}
			$('#pd-slides-truck').animate({marginLeft: '+=' + marginLeft}, slider.speed);
			slider.updateData(activeSlide);
		}
	},
	next: function(presentation) {
		if($('.pd-slide.active').next().length) {
			if(presentation.hasClass('all')) {
				var activeSlide = $('.pd-slide:last-child');
				var marginLeft  = $('#pd-slider').innerWidth() * $('.pd-slide.active').nextAll().length;
			} else {
				var activeSlide = $('.pd-slide.active').next();
				var marginLeft  = $('#pd-slider').innerWidth();
			}
			$('#pd-slides-truck').animate({marginLeft: '-=' + marginLeft}, slider.speed);
			slider.updateData(activeSlide);
		}
	},
	elasticSetup: function() {

		$('.pd-elastic').each(function(e) {

			var wrapper = $(this).parent();

			// Elastic parameters
			var elastic = {
				this:   $(this),
				height: docHeight,
				width:  pressentation.widthByRatio(slider.ratio, docHeight),
				scale:  1
			};

			// Set elastic size
			elastic.this.css({
				'width' : elastic.width + 'px',
				'height' : elastic.height + 'px',
				'transform' : 'scale('+elastic.scale+') translateX(-50%) translateY(-50%)',
				'transition' : 'none'
			});

			// Set slides size
			$('.pd-slide', elastic.this).css({
				'width': elastic.width,
				'min-width': elastic.width,
				'max-width':elastic.width
			});

			// Elastic container scale parameters
			$(window).resize(_.debounce(function() {
				var scaleX = wrapper.innerWidth() / elastic.width;
				var scaleY = wrapper.innerHeight() / elastic.height;

				elastic.scale  = (scaleX > scaleY) ? scaleY : scaleX;

				elastic.this.css({
					'transform' : 'scale('+(elastic.scale).toFixed(4)+') translateX(-50%) translateY(-50%)',
					'transition' : 'transform 0.3s'
				});
			}, 150));
		});

		// Window resize trigger
		$(window).trigger('resize');
	}
};

////////////////////////////////////////////////////////// Document ready

jQuery(document).ready(function($) {
	slider.slide.preview();
});

////////////////////////////////////////////////////////// Slider nav

$(document).on('click', '.pd-nav', function(e) {

	// var activeSlide = $('.pd-slide.active');

	// Open
	if($(this).hasClass('open'))
		pressentation.open($(this));

	// Close
	if($(this).hasClass('close'))
		pressentation.close();

	// Prev
	if($(this).hasClass('prev') && !$('#pd-slides-truck').is(':animated'))
		pressentation.prev($(this))

	// Next
	if($(this).hasClass('next') && !$('#pd-slides-truck').is(':animated'))
		pressentation.next($(this))

});

////////////////////////////////////////////////////////// Keys

$(document).keyup(function(e) {
	switch(e.keyCode) {
		case 27: $('.pd-nav.close').trigger('click'); break;
		case 37: $('.pd-nav.prev:not(.all)').trigger('click'); break;
		case 38: $('.pd-nav.prev.all').trigger('click'); break;
		case 39: $('.pd-nav.next:not(.all)').trigger('click'); break;
		case 40: $('.pd-nav.next.all').trigger('click'); break;
	}
});

$(document).on('click', '.pd-warning-trigger', function() {
	$(this).closest('#pd-warning').toggleClass('show');
});