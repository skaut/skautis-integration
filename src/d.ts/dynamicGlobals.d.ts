declare interface Window {
	rulesOptions: Record<number, string>|undefined;
	rulesData: Array<Record<string, any>>|undefined;
	visibilityOptions: Array<{text: string, value: string}>|undefined;
	skautisQueryBuilderFilters: Array<Record<string, string>>|undefined;
}
