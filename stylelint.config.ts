/** @type {import('stylelint').Config} */
export default {
	extends: [
		'stylelint-config-standard',
		'@wordpress/stylelint-config/stylistic',
	],
	rules: {
		'color-function-notation': 'legacy',
	},
};
