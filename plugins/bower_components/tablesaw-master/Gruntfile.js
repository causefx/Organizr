'use strict';

module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		// Metadata.
		pkg: grunt.file.readJSON('package.json'),
		banner: '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' +
			'<%= grunt.template.today("yyyy-mm-dd") %>\n' +
			'<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
			'* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.company %>;' +
			' Licensed <%= pkg.license %> */\n',
		// Task configuration.
		clean: {
			dependencies: ['dist/dependencies/'],
			post: ['dist/tmp/', 'dist/**/*.min.*']
		},
		copy: {
			jquery: {
				src: 'node_modules/jquery/dist/jquery.js',
				dest: 'dist/dependencies/jquery.js'
			},
			respond: {
				src: 'node_modules/respond.js/dest/respond.src.js',
				dest: 'dist/dependencies/respond.js'
			},
			qunit: {
				files: [{
					expand: true,
					flatten: true,
					src: [ 'node_modules/qunitjs/qunit/*' ],
					dest: 'dist/dependencies/',
					filter: 'isFile'
				}]
			}
		},
		concat: {
			options: {
				banner: '<%= banner %>',
				stripBanners: true
			},
			jsautoinit: {
				src: ['src/tables-init.js'],
				dest: 'dist/<%= pkg.name %>-init.js'
			},
			jsall: {
				src: [
					'src/tables.js',
					'src/tables.stack.js',
					'src/tables.btnmarkup.js',
					'src/tables.columntoggle.js',
					'src/tables.swipetoggle.js',
					'src/tables.sortable.js',
					'src/tables.minimap.js',
					'src/tables.modeswitch.js'
				],
				dest: 'dist/<%= pkg.name %>.js'
			},
			jsstack: {
				src: [
					'src/tables.js',
					'src/tables.stack.js'
				],
				dest: 'dist/stackonly/<%= pkg.name %>.stackonly.js'
			},
			cssall: {
				src: [
					'src/tables.css',
					'src/tables.toolbar.css',
					'src/tables.skin.css',
					'src/tables.stack.css',
					'src/tables.stack-default-breakpoint.css',
					'src/tables.swipetoggle.css',
					'src/tables.columntoggle.css',
					'src/tables.sortable.css',
					'src/tables.minimap.css'
				],
				dest: 'dist/tmp/<%= pkg.name %>.myth.css'
			},
			cssbare: {
				src: [
					'src/tables.css',
					'src/tables.toolbar.css',
					'src/tables.stack.css',
					'src/tables.stack-default-breakpoint.css',
					'src/tables.swipetoggle.css',
					'src/tables.columntoggle.css',
					'src/tables.sortable.css',
					'src/tables.minimap.css',
					'src/tables.modeswitch.css'
				],
				dest: 'dist/tmp/<%= pkg.name %>.bare.myth.css'
			},
			cssstack: {
				src: [
					'src/tables.css',
					'src/tables.stack.css',
					'src/tables.stack-default-breakpoint.css'
				],
				dest: 'dist/tmp/<%= pkg.name %>.stackonly.myth.css'
			},
			cssstackmixinpre: {
				src: [
					'src/tables.css',
					'src/tables.stack.css'
				],
				dest: 'dist/tmp/<%= pkg.name %>.stackonly.myth.scss'
			},
			cssstackmixinpost: {
				src: [
					'dist/tmp/<%= pkg.name %>.stackonly-sans-mixin.scss',
					'src/tables.stack-mixin.scss'
				],
				dest: 'dist/stackonly/<%= pkg.name %>.stackonly.scss'
			}
		},
		qunit: {
			files: ['test/**/*.html']
		},
		jshint: {
			gruntfile: {
				options: {
					jshintrc: '.jshintrc'
				},
				src: 'Gruntfile.js'
			},
			src: {
				options: {
					jshintrc: 'src/.jshintrc'
				},
				src: ['src/**/*.js']
			},
			test: {
				options: {
					jshintrc: 'test/.jshintrc'
				},
				src: ['test/**/*.js']
			},
		},
		watch: {
			gruntfile: {
				files: '<%= jshint.gruntfile.src %>',
				tasks: ['jshint:gruntfile']
			},
			src: {
				files: ['<%= concat.cssall.src %>', '<%= concat.jsall.src %>', '<%= concat.jsautoinit.src %>'],
				tasks: ['src']
			},
			test: {
				files: '<%= jshint.test.src %>',
				tasks: ['jshint:test', 'qunit']
			},
		},
		uglify: {
			js: {
				files: {
					'dist/<%= pkg.name %>.min.js': [ 'dist/<%= pkg.name %>.js' ],
					'dist/stackonly/<%= pkg.name %>.stackonly.min.js': [ 'dist/stackonly/<%= pkg.name %>.stackonly.js' ]
				}
			}
		},
		cssmin: {
			css: {
				files: {
					'dist/<%= pkg.name %>.min.css': [ 'dist/<%= pkg.name %>.css' ],
					'dist/bare/<%= pkg.name %>.bare.min.css': [ 'dist/bare/<%= pkg.name %>.bare.css' ],
					'dist/stackonly/<%= pkg.name %>.stackonly.min.css': [ 'dist/stackonly/<%= pkg.name %>.stackonly.css' ]
				}
			}
		},
		bytesize: {
			dist: {
				src: [
					'dist/<%= pkg.name %>.min.css',
					'dist/<%= pkg.name %>.min.js',
					'dist/bare/<%= pkg.name %>.bare.min.css',
					'dist/stackonly/<%= pkg.name %>.stackonly.min.css',
					'dist/stackonly/<%= pkg.name %>.stackonly.min.js'
				]
			}
		},
		'gh-pages': {
			options: {},
			src: ['dist/**/*', 'demo/**/*', 'test/**/*']
		},
		myth: {
			dist: {
				files: {
					'dist/<%= pkg.name %>.css': '<%= concat.cssall.dest %>',
					'dist/bare/<%= pkg.name %>.bare.css': '<%= concat.cssbare.dest %>',
					'dist/stackonly/<%= pkg.name %>.stackonly.css': '<%= concat.cssstack.dest %>',
					'dist/tmp/<%= pkg.name %>.stackonly-sans-mixin.scss': '<%= concat.cssstackmixinpre.dest %>'
				}
			}
		},
		compress: {
			main: {
				options: {
					archive: 'dist/tablesaw-<%= pkg.version %>.zip',
					mode: 'zip',
					pretty: true
				},
				files: [
					{expand: true, cwd: 'dist/', src: ['*'], dest: 'tablesaw/'},
					{expand: true, cwd: 'dist/', src: ['dependencies/*'], dest: 'tablesaw/'},
					{expand: true, cwd: 'dist/', src: ['stackonly/*'], dest: 'tablesaw/'},
					{expand: true, cwd: 'dist/', src: ['bare/*'], dest: 'tablesaw/'}
				]
			}
		}
	});

	require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

	// Default task.
	grunt.registerTask('travis', ['jshint', 'qunit']);
	grunt.registerTask('concat-pre', ['concat:jsautoinit', 'concat:jsall', 'concat:jsstack', 'concat:cssall', 'concat:cssbare', 'concat:cssstack', 'concat:cssstackmixinpre']);
	grunt.registerTask('concat-post', ['concat:cssstackmixinpost']);
	grunt.registerTask('src', ['concat-pre', 'myth', 'concat-post', 'clean:dependencies', 'copy', 'clean:post']);
	grunt.registerTask('filesize', ['uglify', 'cssmin', 'bytesize', 'clean:post']);

	grunt.registerTask('default', ['jshint', 'src', 'qunit', 'filesize']);

	// Deploy
	grunt.registerTask('deploy', ['default', 'gh-pages']);

};
