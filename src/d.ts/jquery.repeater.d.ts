interface JQuery {
	repeater: JQueryRepeater;
}

interface JQueryRepeater {
	(fig: JQueryRepeaterOptions): JQueryRepeater;
	setList(rows: Array<Record<string, any>>): void;
}

interface JQueryRepeaterOptions {
	defaultValues?: Record<string, any>;
	hide?(deleteElement: () => void): void;
	initEmpty?: boolean;
	isFirstItemUndeletable?: boolean;
	ready?(setIndexes: () => void): void;
	show?(): void;
}
