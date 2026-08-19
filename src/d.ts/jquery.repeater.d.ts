interface JQueryRepeaterOptions {
	defaultValues?: Record<string, any>;
	hide?: (deleteElement: () => void) => void;
	initEmpty?: boolean;
	isFirstItemUndeletable?: boolean;
	ready?: (setIndexes: () => void) => void;
	show?: () => void;
}

interface JQueryRepeater {
	(fig: JQueryRepeaterOptions): JQueryRepeater;
	setList: (rows: Array<Record<string, any>>) => void;
}

interface JQuery {
	repeater: JQueryRepeater;
}
