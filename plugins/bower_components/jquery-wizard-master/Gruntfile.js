'use strict';

module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        // Metadata.
        pkg: grunt.file.readJSON('package.json'),
        banner: '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' + '<%= grunt.template.today("yyyy-mm-dd") %>\n' + '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' + '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' + ' Licensed <%= _.pluck(pkg.licenses, "type").join(", ") %> */\n',
        // Task configuration.

        // -- clean config ----------------------------------------------------------=
        clean: {
            files: ['dist']
        },

        // -- concat config ----------------------------------------------------------
        concat: {
            options: {
                banner: '<%= banner %>',
                stripBanners: true,
                process: true
            },
            dist: {
                src: [
                    'src/intro.js',
                    'src/support.js',
                    'src/setup.js',
                    'src/util.js',
                    'src/defaults.js',
                    'src/step.js',
                    'src/public.js',
                    'src/bind.js',
                    'src/outro.js'
                ],
                dest: 'dist/jquery-wizard.js'
            }
        },

        // -- uglify config ----------------------------------------------------------
        uglify: {
            options: {
                banner: '<%= banner %>'
            },
            dist: {
                src: '<%= concat.dist.dest %>',
                dest: 'dist/jquery-wizard.min.js'
            },
        },

        // -- jshint config ----------------------------------------------------------
        jshint: {
            gruntfile: {
                options: {
                    jshintrc: '.jshintrc'
                },
                src: 'Gruntfile.js'
            },
            dist: {
                options: {
                    jshintrc: 'src/.jshintrc'
                },
                src: ["<%= concat.dist.dest %>"]
            }
        },

        // -- jsbeautifier config -----------------------------------------------------
        jsbeautifier: {
            dist: {
                src: ["<%= concat.dist.dest %>"]
            },
            source: {
                src: ['Gruntfile.js', "src/*.js"],
            },
            options: {
                "indent_size": 4,
                "indent_char": " ",
                "indent_level": 0,
                "indent_with_tabs": false,
                "preserve_newlines": true,
                "max_preserve_newlines": 10,
                "jslint_happy": false,
                "brace_style": "collapse",
                "keep_array_indentation": false,
                "keep_function_indentation": false,
                "space_before_conditional": true,
                "eval_code": false,
                "indent_case": false,
                "unescape_strings": false
            }
        },

        // -- less config ----------------------------------------------------------
        less: {
            dist: {
                files: {
                    'css/wizard.css': ['less/wizard.less']
                }
            }
        },

        // -- autoprefixer config ----------------------------------------------------------
        autoprefixer: {
            options: {
                browsers: [
                    "Android 2.3",
                    "Android >= 4",
                    "Chrome >= 20",
                    "Firefox >= 24",
                    "Explorer >= 8",
                    "iOS >= 6",
                    "Opera >= 12",
                    "Safari >= 6"
                ]
            },
            src: {
                expand: true,
                cwd: 'css/',
                src: ['*.css', '!*.min.css'],
                dest: 'css/'
            }
        },

        // -- watch config ------------------------------------------------------------
        watch: {
            gruntfile: {
                files: '<%= jshint.gruntfile.src %>',
                tasks: ['jshint:gruntfile']
            },
            src: {
                files: '<%= concat.dist.src %>',
                tasks: ['dist']
            }
        },

        // -- csscomb config ---------------------------------------------------------
        csscomb: {
            options: {
              config: '.csscomb.json'
            },
            dist: {
                files: {
                    'css/wizard.css': ['css/wizard.css'],
                },
            }
        },

        // -- replace config ---------------------------------------------------------
        replace: {
            bower: {
                src: ['bower.json'],
                overwrite: true, // overwrite matched source files
                replacements: [{
                    from: /("version": ")([0-9\.]+)(")/g,
                    to: "$1<%= pkg.version %>$3"
                }]
            }
        }
    });

    // Load npm plugins to provide necessary tasks.
    require('load-grunt-tasks')(grunt, {
        pattern: ['grunt-*']
    });

    // Default task.
    grunt.registerTask('default', ['dist', 'jshint']);

    grunt.registerTask('dist', ['clean', 'concat', 'jsbeautifier:dist', 'uglify']);
    grunt.registerTask('js', ['jsbeautifier', 'jshint']);

    grunt.registerTask('version', [
        'replace:bower'
    ]);

    grunt.registerTask('css', ['less', 'csscomb', 'autoprefixer']);
};
