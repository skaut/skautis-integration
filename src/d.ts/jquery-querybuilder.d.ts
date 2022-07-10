interface QueryBuilderRuleOrGroup {
	$el: JQuery;
	parent: QueryBuilderGroup;
	level: number;
	id: string;
	error: string;
	data: object;

	isRoot(): boolean;
	getPos(): number;
	drop(): void;
	moveAfter(_1: QueryBuilderRule|QueryBuilderGroup): void;
	moveAtBegin(_1: QueryBuilderGroup): void;
	moveAtEnd(_1: QueryBuilderGroup): void;
}

interface QueryBuilderRule extends QueryBuilderRuleOrGroup {
	filter: object;
	operator: QueryBuilderOperator;
	value: any;
	flags: object;
}

interface QueryBuilderOperator {
	type: string;
	optgroup: string;
	nb_inputs: number;
	multiple: boolean;
	apply_to: Array<'string'|'number'|'datetime'|'boolean'>
}

interface QueryBuilderGroup extends QueryBuilderRuleOrGroup {
	condition: string;

	empty(): void;
	length(): number;
	addGroup(_1: JQuery, _2: number): QueryBuilderGroup;
	addRule(_1: JQuery, _2: number): QueryBuilderRule;
	each(..._1: any): void;
	contains(_1: QueryBuilderRule|QueryBuilderGroup, _2: boolean): boolean;
}

interface QueryBuilderValidation {
	format?: string|RegExp;
	min?: number|string;
	max?: number|string;
	step?: number;
	messages?: Record<keyof QueryBuilderValidation, string>
	allow_empty_value?: boolean;
	callback?: (value: any, rule: QueryBuilderRule) => true|string
}

interface QueryBuilderOptions {
	// TODO
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
	custom: any; // TODO: Remove
}

interface QueryBuilderExportRule {
	id: string;
	field: string;
	type: string;
	input: string;
	operator: string;
	value: any;
}

interface QueryBuilderExportGroup {
	condition: string;
	rules: Array<QueryBuilderExportRule | QueryBuilderExportGroup>
}

interface QueryBuilderExport extends QueryBuilderExportGroup {
	valid: boolean;
}

interface QueryBuilderJQuery {
	(options: QueryBuilderOptions): JQuery;
	regional: Record<string, QueryBuilderRegional>;
	defaults(options: QueryBuilderOptions): void;

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
