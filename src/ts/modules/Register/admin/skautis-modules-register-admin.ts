(($): void => {
	function updateAvailableOptions(): void {
		const usedOptions: Array<string> = [];

		setTimeout(() => {
			const $selectRules = jQuery('#repeater').find('select.rule');

			$selectRules.each(function () {
				usedOptions.push(jQuery(this).val() as string);
			});

			$selectRules.find('option').removeAttr('disabled');

			for (const item of usedOptions) {
				$selectRules
					.find(`option[value="${item}"]`)
					.attr('disabled', 'disabled');
			}

			$selectRules.each(function () {
				jQuery(this).find('option:selected').removeAttr('disabled');
			});

			reinitSelect2();
		}, 0);
	}

	function reinitSelect2(): void {
		jQuery('#repeater')
			.find('select.select2')
			.select2({
				placeholder: skautisIntegrationRulesSelectLocalize.placeholder,
				width: '100%',
			})
			.on(
				'change.skautis_modules_register_admin',
				updateAvailableOptions
			);
	}

	const $repeater = $('#repeater').repeater({
		defaultValues: {
			role: $('select[name="role"]')
				.first()
				.find('option:selected')
				.val(),
		},
		hide(deleteElement) {
			$(this).slideUp(150, deleteElement);
			setTimeout(() => {
				if (!$('#repeater').find('[data-repeater-item]').length) {
					// The default role is only used when there are no rules.
					$('.skautis-register-default-role').show();
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
		initEmpty: true,
		isFirstItemUndeletable: true,
		ready: (setIndexes) => {
			$('#repeater').on(
				'skautis_modules_register_SortableDrop',
				setIndexes
			);
		},
		show() {
			$(this).slideDown(150);
			if ($('#repeater').find('[data-repeater-item]').length) {
				$('.skautis-register-default-role').hide();
				$('#skautis_integration_modules_register_rulesNotSetHelp').hide(
					400
				);
				$('#skautis_integration_modules_register_rulesSetHelp').show(
					400
				);
			}
			updateAvailableOptions();
		},
	});
	$repeater.setList(window.rulesData ?? []);

	$('[data-repeater-list]').sortable({
		handle: '.handle',
		update: () => {
			$('#repeater').trigger('skautis_modules_register_SortableDrop');
		},
	});
})(jQuery);
