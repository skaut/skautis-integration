(function ($) {
	'use strict';

	var $repeater = $('#repeater_post');

	if ($repeater.length) {
		$repeater.repeater({
			initEmpty: true,
			defaultValues: {
				'role': $('select[name="skautis-integration_rules"]').first().find('option:selected').val()
			},
			show: function () {
				$(this).slideDown(150);
				reinitSelect2();
			},
			hide: function (deleteElement) {
				$(this).slideUp(150, deleteElement);
			},
			ready: function (setIndexes) {
				$repeater.on('skautis_modules_visibility_SortableDrop', setIndexes);
				reinitSelect2();
			},
			isFirstItemUndeletable: true
		});
		$repeater.setList(window.rulesData);

		$('[data-repeater-list]').sortable({
			handle: '.handle',
			update: function () {
				$repeater.trigger('skautis_modules_visibility_SortableDrop');
			}
		});

	} else {
		reinitSelect2();
	}

	function reinitSelect2() {
		jQuery('select.select2').select2({
			placeholder: 'Vyberte pravidlo...'
		});
	}

})(jQuery);