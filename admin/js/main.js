var $ = jQuery.noConflict();

var pressenter = {
	savePresentation: function(form) {
		var data = new FormData(form);

		$('#save-presentation').addClass('load');

		$.post({
			url: ajaxurl,
			data: data,
			contentType: false,
			processData:false
		})
		.done(function(response) {
			if(response.success)
				window.location = '/wp-admin/admin.php?page=pressentation&id='+response.data.ID+'&action=edit';
			else alert('Failed to create the presentation');

			$('#save-presentation').removelass('load');
		})
		.fail(function(response) {
			console.log('error');

			$('#save-presentation').removelass('load');
		});

		return false;
	}
}

// Presentation settings

$(document).on('click', '#presentation-details', function(e) {
	e.preventDefault();
	if($('#presentation-settings-wrapper').is(':visible')) {
		$('#presentation-settings-wrapper').slideUp('fast');
		$('#presentation-details span:last-child').removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
		$('#presentation-settings-header #publish, #presentation-settings-header #save').removeClass('show');
	} else {
		$('#presentation-settings-wrapper').slideDown('fast');
		$('#presentation-details span:last-child').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
		$('#presentation-settings-header #publish, #presentation-settings-header #save').addClass('show');
	}
});