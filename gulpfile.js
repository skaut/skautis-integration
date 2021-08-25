const gulp = require( 'gulp' );

const merge = require( 'merge-stream' );
const replace = require( 'gulp-replace' );
const shell = require( 'gulp-shell' );

gulp.task(
	'build:deps:composer:scoper',
	shell.task(
		'vendor/bin/php-scoper add-prefix --force --output-dir=dist/vendor'
	)
);

gulp.task(
	'build:deps:composer:autoloader',
	gulp.series(
		shell.task(
			'composer dump-autoload --no-dev' +
				( process.env.NODE_ENV === 'production' ? ' -o' : '' )
		),
		function () {
			return merge(
				gulp.src( [
					'vendor/composer/autoload_classmap.php',
					//'vendor/composer/autoload_files.php',
					'vendor/composer/autoload_namespaces.php',
					'vendor/composer/autoload_psr4.php',
				] ),
				gulp
					.src( [ 'vendor/composer/autoload_static.php' ] )
					.pipe(
						replace(
							/class ComposerStaticInit(.*)\n{/,
							'class ComposerStaticInit$1\n{\n    public static $files = array ();'
						)
					)
					.pipe(
						replace(
							'namespace Composer\\Autoload;',
							'namespace SkautisIntegration\\Vendor\\Composer\\Autoload;'
						)
					)
					.pipe(
						replace(
							/'(.*)\\\\' => \n/g,
							"'SkautisIntegration\\\\Vendor\\\\$1\\\\' => \n"
						)
					)
			).pipe( gulp.dest( 'dist/vendor/composer/' ) );
		},
		shell.task( 'composer dump-autoload' )
	)
);

gulp.task(
	'build:deps:composer',
	gulp.series(
		'build:deps:composer:scoper',
		'build:deps:composer:autoloader'
	)
);

gulp.task( 'build:deps:npm:datatables.net:files', function () {
	return gulp
		.src( ['node_modules/datatables.net-dt/images/sort_asc.png', 'node_modules/datatables.net-dt/images/sort_desc.png', 'node_modules/datatables.net-plugins/i18n/cs.json'] )
		.pipe( gulp.dest( 'dist/bundled/datatables-files' ) );
} );

gulp.task( 'build:deps:npm:datatables.net', gulp.parallel( 'build:deps:npm:datatables.net:files', function () {
	return gulp
		.src( ['node_modules/datatables.net-dt/css/jquery.dataTables.min.css', 'node_modules/datatables.net/js/jquery.dataTables.min.js'] )
		.pipe( gulp.dest( 'dist/bundled/' ) );
} ) );

gulp.task( 'build:deps:npm:font-awesome:css', function () {
	return gulp
		.src( 'node_modules/font-awesome/css/font-awesome.min.css' )
		.pipe( gulp.dest( 'dist/bundled/font-awesome/css' ) );
} );

gulp.task( 'build:deps:npm:font-awesome:fonts', function () {
	return gulp
		.src( [
			'node_modules/font-awesome/fonts/fontawesome-webfont.eot',
			'node_modules/font-awesome/fonts/fontawesome-webfont.woff2',
			'node_modules/font-awesome/fonts/fontawesome-webfont.woff',
			'node_modules/font-awesome/fonts/fontawesome-webfont.ttf',
			'node_modules/font-awesome/fonts/fontawesome-webfont.svg',
		] )
		.pipe( gulp.dest( 'dist/bundled/font-awesome/fonts' ) );
} );

gulp.task(
	'build:deps:npm:font-awesome',
	gulp.parallel(
		'build:deps:npm:font-awesome:css',
		'build:deps:npm:font-awesome:fonts',
	)
);

gulp.task( 'build:deps:npm:interactjs', function () {
	return gulp
		.src( 'node_modules/interactjs/dist/interact.min.js' )
		.pipe( gulp.dest( 'dist/bundled/' ) );
} );

gulp.task( 'build:deps:npm:jquery.repeater', function () {
	return gulp
		.src( 'node_modules/jquery.repeater/jquery.repeater.min.js' )
		.pipe( gulp.dest( 'dist/bundled/' ) );
} );

gulp.task( 'build:deps:npm:jQuery-QueryBuilder', function () {
	return gulp
		.src( [ 'node_modules/jQuery-QueryBuilder/dist/css/query-builder.default.min.css', 'node_modules/jQuery-QueryBuilder/dist/js/query-builder.standalone.min.js' ] )
		.pipe( gulp.dest( 'dist/bundled/' ) );
} );

gulp.task( 'build:deps:npm:select2', function () {
	return gulp
		.src( ['node_modules/select2/dist/css/select2.min.css', 'node_modules/select2/dist/js/select2.min.js'] )
		.pipe( gulp.dest( 'dist/bundled/' ) );
} );

gulp.task(
	'build:deps:npm',
	gulp.parallel(
		'build:deps:npm:datatables.net',
		'build:deps:npm:font-awesome',
		'build:deps:npm:interactjs',
		'build:deps:npm:jquery.repeater',
		'build:deps:npm:jQuery-QueryBuilder',
		'build:deps:npm:select2',
	)
);

gulp.task(
	'build:deps',
	gulp.parallel( 'build:deps:composer', 'build:deps:npm' )
);

gulp.task( 'build:php:base', function () {
	return gulp
		.src( [ 'src/*.php' ] )
		.pipe( gulp.dest( 'dist/' ) );
} );

gulp.task( 'build:php:other', function () {
	// TODO: Split these
	return gulp
		.src( [ 'src/**/*.css', 'src/**/*.js', 'src/**/*.php', 'src/**/*.png', 'src/**/*.txt' ] )
		.pipe( gulp.dest( 'dist/' ) );
} );

gulp.task(
	'build:php',
	gulp.parallel(
		'build:php:base',
		'build:php:other'
	)
);

gulp.task(
	'build',
	gulp.parallel(
		'build:deps',
		'build:php'
	)
);
