interface QueryBuilderOptions {
}

interface QueryBuilderRegional {
	"__locale": string;
	"__author": string;
	add_rule: string;
	add_group: string;
	delete_rule: string;
	delete_group: string;
	conditions: {
		AND: string;
		OR: string;
	};
	operators: {
		equal: string;
		not_equal: string;
		in: string;
		not_in: string;
		less: string;
		less_or_equal: string;
		greater: string;
		greater_or_equal: string;
		between: string;
		begins_with: string;
		not_begins_with: string;
		contains: string;
		not_contains: string;
		ends_with: string;
		not_ends_with: string;
		is_empty: string;
		is_not_empty: string;
		is_null: string;
		is_not_null: string;
		any: string;
	};
	errors: {
		no_filter: string;
		empty_group: string;
		radio_empty: string;
		checkbox_empty: string;
		select_empty: string;
		string_empty: string;
		string_exceed_min_length: string;
		string_exceed_max_length: string;
		string_invalid_format: string;
		number_nan: string;
		number_not_integer: string;
		number_not_double: string;
		number_exceed_min: string;
		number_exceed_max: string;
		number_wrong_step: string;
		datetime_empty: string;
		datetime_invalid: string;
		datetime_exceed_min: string;
		datetime_exceed_max: string;
		boolean_not_valid: string;
		operator_not_multiple: string;
	};
	invert: string;
	custom: any;
}

interface JQueryQueryBuilder {
	(options: QueryBuilderOptions): void;
	regional: Record<string, QueryBuilderRegional>;
	defaults(options: QueryBuilderOptions): void;
}

interface JQuery {
	queryBuilder: JQueryQueryBuilder;
}
