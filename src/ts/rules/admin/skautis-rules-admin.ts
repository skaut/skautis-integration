(function ($) {
	'use strict';

	$('#skautis-integration_rules_metabox').hide();
	$('#postdivrich').hide();

	var $form = $('#post');
	var $queryBuilder = $('#query_builder');
	var $queryBuilderValues = $('#query_builder_values');

	if ($queryBuilder.length) {
		var rules = null;
		var values = $queryBuilderValues.val();
		if (typeof values === "string" && values.length > 0) {
			rules = JSON.parse(values);
		} else {
			rules = null;
		}

		$queryBuilder.queryBuilder({
			plugins: {
				'sortable': {
					icon: 'fa fa-arrows-alt'
				},
				'filter-description': {
					icon: 'fa fa-info-circle',
					mode: 'inline'
				},
				'unique-filter': null
			},
			allow_empty: true,
			rules: rules,
			icons: {
				add_group: 'fa fa-plus-square',
				add_rule: 'fa fa-plus-circle',
				remove_group: 'fa fa-minus-square',
				remove_rule: 'fa fa-minus-circle',
				error: 'fa fa-exclamation-triangle'
			},
			filters: window.skautisQueryBuilderFilters
		});

		$queryBuilder.on('change', function () {
			$(this).find('select[multiple]:not(.select2-hidden-accessible)').each(function () {
				$(this).select2({
					placeholder: jQuery.fn.queryBuilder.regional.cs.custom.select_placeholder,
					sorter: function (data) {
						return data.sort(function (a, b) {
							if (typeof a.text.localeCompare === 'function') {
								return a.text.localeCompare(b.text);
							} else {
								return a.text > b.text ? 1 : 0;
							}
						});
					}
				});
			});
		});
		setTimeout(function () {
			$queryBuilder.trigger('change');
		}, 100);

		$form.on('submit', function () {
			var result = $queryBuilder.queryBuilder('getRules');

			if (!$.isEmptyObject(result)) {
				$queryBuilderValues.val(JSON.stringify(result, null, 2));
			}

			tinymce.activeEditor.setContent($queryBuilderValues.val() as string);

			return !$queryBuilder.find('.has-error').length;

		});

		$queryBuilder.on('change.skautis_rules_ui_helper', function () {
			$queryBuilder.off('change.skautis_rules_ui_helper');
			setTimeout(function () {
				$queryBuilder.on('change.skautis_rule_unitnumber_select', '.skautis-rule-unitnumber-select', function () {
					var $input = jQuery(this).parent().find('.skautis-rule-unitnumber-input');
					if (jQuery(this).val() === 'any') {
						$input.fadeOut();
						if ($input.val() === '') {
							$input.val('0').trigger('change');
						}
					} else {
						$input.fadeIn();
					}
				});
			}, 100);
		});

		$queryBuilder.find('.skautis-rule-unitnumber-select').each(function () {
			if (jQuery(this).val() === 'any') {
				jQuery(this).parent().find('.skautis-rule-unitnumber-input').hide();
			}
		});
	}
})(jQuery);
