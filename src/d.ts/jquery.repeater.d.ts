interface JQuery {
	repeater: JQueryRepeater;
}

interface JQueryRepeater {
	(fig: JQueryRepeaterOptions): JQueryRepeater;
	setList(rows: Array<Record<string, JQueryRepeaterValue>>): void;
}

interface JQueryRepeaterOptions {
	defaultValues?: Record<string, JQueryRepeaterValue>;
	hide?(deleteElement: () => void): void;
	initEmpty?: boolean;
	isFirstItemUndeletable?: boolean;
	ready?(setIndexes: () => void): void;
	show?(): void;
}

type JQueryRepeaterValue = Array<string> | number | string | undefined;
