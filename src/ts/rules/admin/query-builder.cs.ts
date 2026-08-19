/*!
 * jQuery QueryBuilder 2.4.3
 * Locale: Čeština (cs)
 * Author: David Odehnal
 * Licensed under MIT (http://opensource.org/licenses/MIT)
 */
const QueryBuilder = jQuery.fn.queryBuilder;

QueryBuilder.regional['cs'] = {
	__author: 'David Odehnal',
	__locale: 'Čeština (cs)',
	add_group: 'Přidat skupinu',
	add_rule: 'Přidat podmínku',
	conditions: {
		AND: 'A zároveň',
		OR: 'Nebo',
	},
	custom: {
		select_placeholder: 'Vyberte...',
		units: {
			inUnitWithNumber: 'v jednotce, jejíž evidenční číslo',
			unitNumber: 'číslo jednotky (např. 411.12)',
		},
	},
	delete_group: 'Odstranit skupinu',
	delete_rule: 'Odstranit podmínku',
	errors: {
		boolean_not_valid: 'Musí být zadán logický výraz',
		checkbox_empty: 'Není zadána hodnota',
		datetime_empty: 'Nevyplněno',
		datetime_exceed_max: 'Musí být do {0}',
		datetime_exceed_min: 'Musí být po {0}',
		datetime_invalid: 'Nesprávný formát datumu ({0})',
		empty_group: 'skupina podmínek je prázdná',
		no_filter: 'není vybrán žádný filtr',
		number_exceed_max: 'Musí být méně {0}',
		number_exceed_min: 'Musí být více {0}',
		number_nan: 'Žádné číslo',
		number_not_double: 'Žádné číslo',
		number_not_integer: 'Žádné číslo',
		number_wrong_step: 'Musí být násobkem {0}',
		operator_not_multiple: "Operátor '{1}' nepodporuje více hodnot",
		radio_empty: 'Není zadána hodnota',
		select_empty: 'Není zadána hodnota',
		string_empty: 'Nevyplněno',
		string_exceed_max_length: 'Musí obsahovat méně {0} symbolů',
		string_exceed_min_length: 'Musí obsahovat více {0} symbolů',
		string_invalid_format: 'Nesprávný formát',
	},
	invert: 'invertní',
	operators: {
		any: 'je jakékoliv',
		begins_with: 'začíná na',
		between: 'je mezi',
		contains: 'obsahuje',
		ends_with: 'končí na',
		equal: 'je rovno',
		greater: 'je větší než',
		greater_or_equal: 'je větší nebo stejné jako',
		in: 'je ve výběru',
		is_empty: 'je prázdné',
		is_not_empty: 'není prázdné',
		is_not_null: 'není vyplněno',
		is_null: 'je vyplněno',
		less: 'je menší než',
		less_or_equal: 'je menší nebo stejné jako',
		not_begins_with: 'nezačíná na',
		not_contains: 'neobsahuje',
		not_ends_with: 'nekončí na',
		not_equal: 'není rovno',
		not_in: 'není ve výběru',
	},
};

QueryBuilder.defaults({ lang_code: 'cs' });
