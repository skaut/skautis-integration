/* eslint-env node */

const gulp = require('gulp');

const cleanCSS = require('gulp-clean-css');
const merge = require('merge-stream');
const rename = require('gulp-rename');
const replace = require('gulp-replace');
const shell = require('gulp-shell');
const terser = require('gulp-terser');
const ts = require('gulp-typescript');

gulp.task('build:css:admin', () =>
	gulp
		.src(['src/css/admin/*.css'])
		.pipe(cleanCSS())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/admin/css/'))
);

gulp.task('build:css:frontend', () =>
	gulp
		.src(['src/css/frontend/*.css'])
		.pipe(cleanCSS())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/frontend/css/'))
);

gulp.task('build:css:modules:Register:admin', () =>
	gulp
		.src(['src/css/modules/Register/admin/*.css'])
		.pipe(cleanCSS())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/modules/Register/admin/css/'))
);

gulp.task(
	'build:css:modules:Register',
	gulp.parallel('build:css:modules:Register:admin')
);

gulp.task('build:css:modules:Shortcodes:admin', () =>
	gulp
		.src(['src/css/modules/Shortcodes/admin/*.css'])
		.pipe(cleanCSS())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/modules/Shortcodes/admin/css/'))
);

gulp.task(
	'build:css:modules:Shortcodes',
	gulp.parallel('build:css:modules:Shortcodes:admin')
);

gulp.task('build:css:modules:Visibility:admin', () =>
	gulp
		.src(['src/css/modules/Visibility/admin/*.css'])
		.pipe(cleanCSS())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/modules/Visibility/admin/css/'))
);

gulp.task(
	'build:css:modules:Visibility',
	gulp.parallel('build:css:modules:Visibility:admin')
);

gulp.task(
	'build:css:modules',
	gulp.parallel(
		'build:css:modules:Register',
		'build:css:modules:Shortcodes',
		'build:css:modules:Visibility'
	)
);

gulp.task('build:css:rules:admin', () =>
	gulp
		.src(['src/css/rules/admin/*.css'])
		.pipe(cleanCSS())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/rules/admin/css/'))
);

gulp.task('build:css:rules', gulp.parallel('build:css:rules:admin'));

gulp.task(
	'build:css',
	gulp.parallel(
		'build:css:admin',
		'build:css:frontend',
		'build:css:modules',
		'build:css:rules'
	)
);

gulp.task(
	'build:deps:composer:scoper',
	shell.task('vendor/bin/php-scoper add-prefix --force')
);

gulp.task(
	'build:deps:composer:autoloader',
	gulp.series(
		shell.task(
			'composer dump-autoload --no-dev' +
				(process.env.NODE_ENV === 'production' ? ' -o' : '')
		),
		() =>
			merge(
				gulp.src([
					'vendor/composer/autoload_classmap.php',
					//'vendor/composer/autoload_files.php',
					'vendor/composer/autoload_namespaces.php',
					'vendor/composer/autoload_psr4.php',
				]),
				gulp
					.src(['vendor/composer/autoload_static.php'])
					.pipe(
						replace(
							/class ComposerStaticInit(.*)\n{/,
							'class ComposerStaticInit$1\n{\n    public static $files = array ();'
						)
					)
					.pipe(
						replace(
							'namespace Composer\\Autoload;',
							'namespace Skautis_Integration\\Vendor\\Composer\\Autoload;'
						)
					)
					.pipe(
						replace(
							/'(.*)\\\\' => \n/g,
							"'Skautis_Integration\\\\Vendor\\\\$1\\\\' => \n"
						)
					)
			).pipe(gulp.dest('dist/vendor/composer/')),
		shell.task('composer dump-autoload')
	)
);

gulp.task(
	'build:deps:composer',
	gulp.series('build:deps:composer:scoper', 'build:deps:composer:autoloader')
);

gulp.task('build:deps:npm:datatables.net:files', () =>
	gulp
		.src([
			'node_modules/datatables.net-dt/images/sort_asc.png',
			'node_modules/datatables.net-dt/images/sort_desc.png',
			'node_modules/datatables.net-plugins/i18n/cs.json',
		])
		.pipe(gulp.dest('dist/bundled/datatables-files'))
);

gulp.task(
	'build:deps:npm:datatables.net',
	gulp.parallel('build:deps:npm:datatables.net:files', () =>
		gulp
			.src([
				'node_modules/datatables.net-dt/css/jquery.dataTables.min.css',
				'node_modules/datatables.net/js/jquery.dataTables.min.js',
			])
			.pipe(gulp.dest('dist/bundled/'))
	)
);

gulp.task('build:deps:npm:font-awesome:css', () =>
	gulp
		.src('node_modules/font-awesome/css/font-awesome.min.css')
		.pipe(gulp.dest('dist/bundled/font-awesome/css'))
);

gulp.task('build:deps:npm:font-awesome:fonts', () =>
	gulp
		.src([
			'node_modules/font-awesome/fonts/fontawesome-webfont.eot',
			'node_modules/font-awesome/fonts/fontawesome-webfont.woff2',
			'node_modules/font-awesome/fonts/fontawesome-webfont.woff',
			'node_modules/font-awesome/fonts/fontawesome-webfont.ttf',
			'node_modules/font-awesome/fonts/fontawesome-webfont.svg',
		])
		.pipe(gulp.dest('dist/bundled/font-awesome/fonts'))
);

gulp.task(
	'build:deps:npm:font-awesome',
	gulp.parallel(
		'build:deps:npm:font-awesome:css',
		'build:deps:npm:font-awesome:fonts'
	)
);

gulp.task('build:deps:npm:interactjs', () =>
	gulp
		.src('node_modules/interactjs/dist/interact.min.js')
		.pipe(gulp.dest('dist/bundled/'))
);

gulp.task('build:deps:npm:jquery.repeater', () =>
	gulp
		.src('node_modules/jquery.repeater/jquery.repeater.min.js')
		.pipe(gulp.dest('dist/bundled/'))
);

gulp.task('build:deps:npm:jQuery-QueryBuilder', () =>
	gulp
		.src([
			'node_modules/jQuery-QueryBuilder/dist/css/query-builder.default.css',
			'node_modules/jQuery-QueryBuilder/dist/js/query-builder.standalone.js',
		])
		.pipe(gulp.dest('dist/bundled/'))
);

gulp.task('build:deps:npm:select2', () =>
	gulp
		.src([
			'node_modules/select2/dist/css/select2.min.css',
			'node_modules/select2/dist/js/select2.min.js',
		])
		.pipe(gulp.dest('dist/bundled/'))
);

gulp.task(
	'build:deps:npm',
	gulp.parallel(
		'build:deps:npm:datatables.net',
		'build:deps:npm:font-awesome',
		'build:deps:npm:interactjs',
		'build:deps:npm:jquery.repeater',
		'build:deps:npm:jQuery-QueryBuilder',
		'build:deps:npm:select2'
	)
);

gulp.task('build:deps', gulp.parallel('build:deps:composer', 'build:deps:npm'));

gulp.task('build:js:admin', () => {
	const tsProject = ts.createProject('tsconfig.json');
	return gulp
		.src(['src/ts/admin/*.ts', 'src/d.ts/*.d.ts'])
		.pipe(tsProject())
		.js.pipe(terser())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/admin/js/'));
});

gulp.task('build:js:modules:Register:admin', () => {
	const tsProject = ts.createProject('tsconfig.json');
	return gulp
		.src(['src/ts/modules/Register/admin/*.ts', 'src/d.ts/*.d.ts'])
		.pipe(tsProject())
		.js.pipe(terser())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/modules/Register/admin/js/'));
});

gulp.task(
	'build:js:modules:Register',
	gulp.parallel('build:js:modules:Register:admin')
);

gulp.task('build:js:modules:Shortcodes:admin', () => {
	const tsProject = ts.createProject('tsconfig.json');
	return gulp
		.src(['src/ts/modules/Shortcodes/admin/*.ts', 'src/d.ts/*.d.ts'])
		.pipe(tsProject())
		.js.pipe(terser())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/modules/Shortcodes/admin/js/'));
});

gulp.task(
	'build:js:modules:Shortcodes',
	gulp.parallel('build:js:modules:Shortcodes:admin')
);

gulp.task('build:js:modules:Visibility:admin', () => {
	const tsProject = ts.createProject('tsconfig.json');
	return gulp
		.src(['src/ts/modules/Visibility/admin/*.ts', 'src/d.ts/*.d.ts'])
		.pipe(tsProject())
		.js.pipe(terser())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/modules/Visibility/admin/js/'));
});

gulp.task(
	'build:js:modules:Visibility',
	gulp.parallel('build:js:modules:Visibility:admin')
);

gulp.task(
	'build:js:modules',
	gulp.parallel(
		'build:js:modules:Register',
		'build:js:modules:Shortcodes',
		'build:js:modules:Visibility'
	)
);

gulp.task('build:js:rules:admin', () => {
	const tsProject = ts.createProject('tsconfig.json');
	return gulp
		.src(['src/ts/rules/admin/*.ts', 'src/d.ts/*.d.ts'])
		.pipe(tsProject())
		.js.pipe(terser())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('dist/rules/admin/js/'));
});

gulp.task('build:js:rules', gulp.parallel('build:js:rules:admin'));

gulp.task(
	'build:js',
	gulp.parallel('build:js:admin', 'build:js:modules', 'build:js:rules')
);

gulp.task('build:php:base', () =>
	gulp.src(['src/php/*.php']).pipe(gulp.dest('dist/'))
);

gulp.task('build:php:other', () =>
	gulp.src(['src/php/**/*.php']).pipe(gulp.dest('dist/'))
);

gulp.task('build:php', gulp.parallel('build:php:base', 'build:php:other'));

gulp.task('build:png', () =>
	gulp.src(['src/png/**/*.png']).pipe(gulp.dest('dist/src/'))
);

gulp.task('build:txt', () =>
	gulp.src(['src/txt/**/*.txt']).pipe(gulp.dest('dist/'))
);

gulp.task(
	'build',
	gulp.parallel(
		'build:css',
		'build:deps',
		'build:js',
		'build:php',
		'build:png',
		'build:txt'
	)
);
