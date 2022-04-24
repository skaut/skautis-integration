(function ($): void {
	const $repeater = $('#repeater_post');

	if ($repeater.length) {
		$repeater
			.repeater({
				initEmpty: true,
				defaultValues: {
					role: $('select[name="skautis-integration_rules"]')
						.first()
						.find('option:selected')
						.val(),
				},
				show() {
					$(this).slideDown(150);
					updateAvailableOptions();
				},
				hide(deleteElement) {
					$(this).slideUp(150, deleteElement);
					setTimeout(function () {
						updateAvailableOptions();
					}, 250);
				},
				ready() {
					reinitSelect2();
				},
				isFirstItemUndeletable: true,
			})
			.setList(window.rulesData ?? []);
	} else {
		reinitSelect2();
	}

	function reinitSelect2(): void {
		jQuery('select.select2')
			.select2({
				placeholder: 'Vyberte pravidlo...',
			})
			.on(
				'change.skautis_modules_visibility_admin',
				updateAvailableOptions
			);
	}

	function updateAvailableOptions(): void {
		const usedOptions: Array<string> = [];

		setTimeout(function () {
			const $selectRules = jQuery('select.rule');

			$selectRules.each(function () {
				const value = jQuery(this).val() as string | null;
				if (value !== null) {
					usedOptions.push(value);
				}
			});

			$selectRules.find('option').removeAttr('disabled');

			const $rulesUsedInParents = jQuery(
				'#skautis_modules_visibility_parentRules'
			).find('li[data-rule]');
			$rulesUsedInParents.each(function () {
				usedOptions.push(($(this).data('rule') as number).toString());
			});

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
