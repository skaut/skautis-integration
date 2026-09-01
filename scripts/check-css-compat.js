import doiuse from 'doiuse';
import { readdir, readFile } from 'node:fs/promises';
import { join } from 'node:path';
import postcss from 'postcss';

const DIST = 'dist';

/*
 * Vendored libraries, copied verbatim from node_modules by build:deps:*. Their
 * CSS is not ours to change and is not routed through Lightning CSS, so it is
 * out of scope here rather than silently suppressed feature by feature.
 */
const SKIP = ['dist/bundled/'];

/*
 * Declarations that trip a partial-support flag while falling outside what the
 * flag actually covers. Each predicate matches only the safe subset, so a value
 * the flag does cover is still reported.
 */
const SAFE = {
	/*
	 * Desktop Safari reports `n`, not partial, but note 5 is "not applicable to
	 * platforms that do not support touch events" - the declaration is inert
	 * exactly where it is unsupported, which is the intended outcome.
	 */
	'css-touch-action': ({ prop }) => prop === 'touch-action',
	/*
	 * This flags the JavaScript Pointer Events API: notes 5 and 6 are about
	 * releasePointerCapture and pointerevent.buttons. The detector also matches
	 * `touch-action`, so it only ever re-reports the declaration above.
	 */
	pointer: ({ prop }) => prop === 'touch-action',
};

/**
 * @param {string} dir
 * @return {Promise<Array<string>>} every .css file below dir, recursively
 */
const cssFiles = async (dir) => {
	const entries = await readdir(dir, { withFileTypes: true });
	const nested = await Promise.all(
		entries.map(async (entry) => {
			const path = join(dir, entry.name);
			if (entry.isDirectory()) {
				return cssFiles(path);
			}
			return entry.name.endsWith('.css') ? [path] : [];
		})
	);
	return nested.flat();
};

// Fail closed: an empty file list makes doiuse report nothing, which reads as a pass.
const files = (await cssFiles(DIST).catch(() => [])).filter(
	(file) => !SKIP.some((prefix) => file.startsWith(prefix))
);
if (files.length === 0) {
	console.error(`No CSS found under ${DIST}/ - run \`npm run build\` first.`);
	process.exit(1);
}

const problems = (
	await Promise.all(
		files.map(async (file) => {
			const found = [];
			const css = await readFile(file, 'utf8');
			await postcss([
				doiuse({
					onFeatureUsage: ({ feature, featureData, usage }) => {
						if (
							Object.hasOwn(SAFE, feature) &&
							usage.type === 'decl' &&
							SAFE[feature](usage)
						) {
							return;
						}
						const what =
							usage.type === 'decl'
								? `${usage.prop}: ${usage.value}`
								: `@${usage.name} ${usage.params}`;
						const { column, line } = usage.source.start;
						found.push(
							`${file}:${line}:${column}  ${what}  (${feature}: ${featureData.missing || featureData.partial})`
						);
					},
				}),
			]).process(css, { from: file });
			return found;
		})
	)
).flat();

if (problems.length > 0) {
	for (const problem of problems) {
		console.error(problem);
	}
	process.exit(1);
}
console.log(`No unsupported features in ${files.length} built CSS file(s).`);
