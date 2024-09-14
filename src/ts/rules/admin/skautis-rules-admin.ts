(function ($): void {
	$('#skautis-integration_rules_metabox').hide();
	$('#postdivrich').hide();

	const $queryBuilderValues = $('#query_builder_values');

	if ($('#query_builder').length) {
		let rules = null;
		const values = $queryBuilderValues.val();
		if (typeof values === 'string' && values.length > 0) {
			rules = JSON.parse(values) as QueryBuilderExport;
		}

		$('#query_builder').queryBuilder({
			plugins: {
				sortable: {
					icon: 'fa fa-arrows-alt',
				},
				'filter-description': {
					icon: 'fa fa-info-circle',
					mode: 'inline',
				},
				'unique-filter': null,
			},
			allow_empty: true,
			rules,
			icons: {
				add_group: 'fa fa-plus-square',
				add_rule: 'fa fa-plus-circle',
				remove_group: 'fa fa-minus-square',
				remove_rule: 'fa fa-minus-circle',
				error: 'fa fa-exclamation-triangle',
			},
			filters: window.skautisQueryBuilderFilters,
		});

		$('#query_builder').on('change', function () {
			$(this)
				.find('select[multiple]:not(.select2-hidden-accessible)')
				.each(function () {
					$(this).select2({
						placeholder:
							skautisIntegrationRulesLocalize.select_placeholder,
						sorter: (data) =>
							data.sort((a, b) => {
								if (
									typeof a.text.localeCompare === 'function'
								) {
									return a.text.localeCompare(b.text);
								}
								return a.text > b.text ? 1 : 0;
							}),
					});
				});
		});
		setTimeout(() => {
			$('#query_builder').trigger('change');
		}, 100);

		$('#post').on('submit', () => {
			const result = $('#query_builder').get(0)?.queryBuilder.getRules();

			if (!$.isEmptyObject(result)) {
				$queryBuilderValues.val(JSON.stringify(result, null, 2));
			}

			tinymce.activeEditor.setContent(
				$queryBuilderValues.val() as string
			);

			return !$('#query_builder').find('.has-error').length;
		});

		$('#query_builder').on('change.skautis_rules_ui_helper', function () {
			$('#query_builder').off('change.skautis_rules_ui_helper');
			setTimeout(function () {
				$('#query_builder').on(
					'change.skautis_rule_unitnumber_select',
					'.skautis-rule-unitnumber-select',
					function () {
						const $input = jQuery(this)
							.parent()
							.find('.skautis-rule-unitnumber-input');
						if (jQuery(this).val() === 'any') {
							$input.fadeOut();
							if ($input.val() === '') {
								$input.val('0').trigger('change');
							}
						} else {
							$input.fadeIn();
						}
					}
				);
			}, 100);
		});

		$('#query_builder')
			.find('.skautis-rule-unitnumber-select')
			.each(function () {
				if (jQuery(this).val() === 'any') {
					jQuery(this)
						.parent()
						.find('.skautis-rule-unitnumber-input')
						.hide();
				}
			});
	}
})(jQuery);
