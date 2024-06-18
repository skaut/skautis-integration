/* eslint-env node */

/** @type {import('stylelint').Config} */
module.exports = {
	extends: '@wordpress/stylelint-config',
	plugins: ['stylelint-no-unsupported-browser-features'],
};
