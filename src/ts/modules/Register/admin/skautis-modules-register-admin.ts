(function ($): void {
	const $repeater = $('#repeater').repeater({
		initEmpty: true,
		defaultValues: {
			role: $('select[name="role"]')
				.first()
				.find('option:selected')
				.val(),
		},
		show() {
			$(this).slideDown(150);
			if ($('#repeater').find('[data-repeater-item]').length) {
				$('.form-table').find('tr').first().find('*').slideUp(200);
				$('#skautis_integration_modules_register_rulesNotSetHelp').hide(
					400
				);
				$('#skautis_integration_modules_register_rulesSetHelp').show(
					400
				);
			}
			updateAvailableOptions();
		},
		hide(deleteElement) {
			$(this).slideUp(150, deleteElement);
			setTimeout(() => {
				if (!$('#repeater').find('[data-repeater-item]').length) {
					$('.form-table')
						.find('tr')
						.first()
						.find('*')
						.slideDown(200);
					$(
						'#skautis_integration_modules_register_rulesNotSetHelp'
					).show(400);
					$(
						'#skautis_integration_modules_register_rulesSetHelp'
					).hide(400);
				}

				updateAvailableOptions();
			}, 250);
		},
		ready: (setIndexes) => {
			$('#repeater').on(
				'skautis_modules_register_SortableDrop',
				setIndexes
			);
		},
		isFirstItemUndeletable: true,
	});
	$repeater.setList(window.rulesData ?? []);

	$('[data-repeater-list]').sortable({
		handle: '.handle',
		update: () => {
			$('#repeater').trigger('skautis_modules_register_SortableDrop');
		},
	});

	function reinitSelect2(): void {
		jQuery('.form-table')
			.find('select.select2')
			.select2({
				placeholder: 'Vyberte pravidlo...',
			})
			.on(
				'change.skautis_modules_register_admin',
				updateAvailableOptions
			);
	}

	function updateAvailableOptions(): void {
		const usedOptions: Array<string> = [];

		setTimeout(function () {
			const $selectRules = jQuery('.form-table').find('select.rule');

			$selectRules.each(function () {
				usedOptions.push(jQuery(this).val() as string);
			});

			$selectRules.find('option').removeAttr('disabled');

			for (const item of usedOptions) {
				$selectRules
					.find('option[value="' + item + '"]')
					.attr('disabled', 'disabled');
			}

			$selectRules.each(function () {
				jQuery(this).find('option:selected').removeAttr('disabled');
			});

			reinitSelect2();
		}, 0);
	}
})(jQuery);
