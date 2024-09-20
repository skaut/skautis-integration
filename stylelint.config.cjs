/* eslint-env node */

/** @type {import('stylelint').Config} */
module.exports = {
	extends: '@wordpress/stylelint-config/stylistic',
	plugins: ['stylelint-no-unsupported-browser-features'],
};
