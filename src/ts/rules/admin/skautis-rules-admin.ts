(($): void => {
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
			allow_empty: true,
			filters: window.skautisQueryBuilderFilters,
			icons: {
				add_group: 'dashicons dashicons-plus-alt',
				add_rule: 'dashicons dashicons-plus',
				error: 'dashicons dashicons-warning',
				remove_group: 'dashicons dashicons-trash',
				remove_rule: 'dashicons dashicons-no-alt',
			},
			plugins: {
				'filter-description': {
					icon: 'dashicons dashicons-info-outline',
					mode: 'inline',
				},
				sortable: {
					icon: 'dashicons dashicons-move',
				},
				'unique-filter': null,
			},
			rules,
		});

		// QueryBuilder creates the fields without labels, so they get accessible names here.
		const labelFields = (): void => {
			const labels: Array<[string, string]> = [
				[
					'.rule-filter-container select',
					skautisIntegrationRulesLocalize.labelCondition,
				],
				[
					'.rule-operator-container select',
					skautisIntegrationRulesLocalize.labelOperator,
				],
				[
					'.rule-value-container select[multiple]',
					skautisIntegrationRulesLocalize.labelValues,
				],
				[
					'.skautis-rule-unitnumber-select',
					skautisIntegrationRulesLocalize.inUnitWithNumber,
				],
				[
					'.skautis-rule-unitnumber-input',
					skautisIntegrationRulesLocalize.unitNumber,
				],
			];
			for (const [selector, label] of labels) {
				$('#query_builder')
					.find(selector)
					.not('[aria-label]')
					.attr('aria-label', label);
			}
		};
		$('#query_builder').on(
			'afterCreateRuleFilters.queryBuilder afterCreateRuleOperators.queryBuilder afterCreateRuleInput.queryBuilder',
			() => {
				setTimeout(labelFields, 0);
			}
		);
		labelFields();

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
					// Select2 replaces the field, so the replacement gets the accessible name of the field.
					$(this)
						.next('.select2')
						.find('.select2-selection, .select2-search__field')
						.attr(
							'aria-label',
							$(this).attr('aria-label') ??
								skautisIntegrationRulesLocalize.labelValues
						);
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

		$('#query_builder').on('change.skautis_rules_ui_helper', () => {
			$('#query_builder').off('change.skautis_rules_ui_helper');
			setTimeout(() => {
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
