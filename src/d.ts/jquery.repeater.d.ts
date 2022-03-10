interface JQueryRepeaterOptions {
	initEmpty?: boolean;
	defaultValues?: Record<string, any>;
	show?: () => void;
	hide?: (deleteElement: () => void) => void;
	ready?: (setIndexes: () => void) => void;
	isFirstItemUndeletable?: boolean;
}

interface JQueryRepeater {
	(fig: JQueryRepeaterOptions): JQueryRepeater;
	setList: (rows: Array<Record<string, any>>) => void;
}

interface JQuery {
	repeater: JQueryRepeater;
}
