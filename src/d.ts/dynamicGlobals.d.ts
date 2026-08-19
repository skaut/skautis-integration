declare interface Window {
	rulesData: Array<Record<string, any>>|undefined;
	rulesOptions: Record<number, string>|undefined;
	skautisQueryBuilderFilters: Array<Record<string, string>>|undefined;
	visibilityOptions: Array<{text: string, value: string}>|undefined;
}
