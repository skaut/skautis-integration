interface QueryBuilderRuleOrGroup {
	$el: JQuery;
	data: object;
	drop(): void;
	error: string;
	getPos(): number;
	id: string;

	isRoot(): boolean;
	level: number;
	moveAfter(_1: QueryBuilderGroup|QueryBuilderRule): void;
	moveAtBegin(_1: QueryBuilderGroup): void;
	moveAtEnd(_1: QueryBuilderGroup): void;
	parent: QueryBuilderGroup;
}

interface QueryBuilderRule extends QueryBuilderRuleOrGroup {
	filter: object;
	flags: object;
	operator: QueryBuilderOperator;
	value: any;
}

interface QueryBuilderOperator {
	apply_to: Array<'boolean'|'datetime'|'number'|'string'>
	multiple: boolean;
	nb_inputs: number;
	optgroup: string;
	type: string;
}

interface QueryBuilderGroup extends QueryBuilderRuleOrGroup {
	addGroup(_1: JQuery, _2: number): QueryBuilderGroup;

	addRule(_1: JQuery, _2: number): QueryBuilderRule;
	condition: string;
	contains(_1: QueryBuilderGroup|QueryBuilderRule, _2: boolean): boolean;
	each(..._1: any): void;
	empty(): void;
	length(): number;
}

interface QueryBuilderValidation {
	allow_empty_value?: boolean;
	callback?: (value: any, rule: QueryBuilderRule) => true|string
	format?: RegExp|string;
	max?: number|string;
	messages?: Record<keyof QueryBuilderValidation, string>
	min?: number|string;
	step?: number;
}

// eslint-disable-next-line @typescript-eslint/no-empty-object-type -- TODO
interface QueryBuilderOptions {
	// TODO
}

interface QueryBuilderRegional {
	"__author": string;
	"__locale": string;
	add_group: string;
	add_rule: string;
	conditions: {
		AND: string;
		OR: string;
	};
	custom: any; // TODO: Remove
	delete_group: string;
	delete_rule: string;
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
}

interface QueryBuilderExportRule {
	field: string;
	id: string;
	input: string;
	operator: string;
	type: string;
	value: any;
}

interface QueryBuilderExportGroup {
	condition: string;
	rules: Array<QueryBuilderExportGroup|QueryBuilderExportRule>
}

interface QueryBuilderExport extends QueryBuilderExportGroup {
	valid: boolean;
}

interface QueryBuilderJQuery {
	defaults(options: QueryBuilderOptions): void;
	regional: Record<string, QueryBuilderRegional>;

	(options: QueryBuilderOptions): JQuery;
	// Methods from QueryBuilderElement
	(methodName: "getRules"): QueryBuilderExport;
	// TODO
}

interface QueryBuilderElement {
	getRules(): QueryBuilderExport;
	// TODO
}

interface JQuery {
	queryBuilder: QueryBuilderJQuery;
}

interface HTMLElement {
	queryBuilder: QueryBuilderElement;
}
