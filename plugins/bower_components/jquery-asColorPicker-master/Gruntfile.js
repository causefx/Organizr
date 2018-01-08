'use strict';

module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        // Metadata.
        pkg: grunt.file.readJSON('package.json'),
        banner: '/*! <%= pkg.title || pkg.name %> - v<%= pkg.version %> - ' + '<%= grunt.template.today("yyyy-mm-dd") %>\n' + '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' + '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' + ' Licensed <%= _.pluck(pkg.licenses, "type").join(", ") %> */\n',
        // Task configuration.
        clean: {
            files: ['dist']
        },
        concat: {
            options: {
                banner: '<%= banner %>',
                stripBanners: true
            },
            dist: {
                src: ['src/core.js', 'src/trigger.js', 'src/clear.js', 'src/keyboard.js', 'src/alpha.js', 'src/buttons.js', 'src/hex.js', 'src/hue.js', 'src/info.js', 'src/palettes.js', 'src/preview.js', 'src/saturation.js', 'src/gradient.js'],
                dest: 'dist/<%= pkg.name %>.js'
            },

        },
        uglify: {
            options: {
                banner: '<%= banner %>'
            },
            dist: {
                src: '<%= concat.dist.dest %>',
                dest: 'dist/<%= pkg.name %>.min.js'
            }
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
            }
        },

        jsbeautifier: {
            files: ['Gruntfile.js', "src/**/*.js"],
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
        watch: {
            less: {
                files: 'less/**/*.less',
                tasks: ['css']
            }
        },

        less: {
            dist: {
                files: {
                    'css/asColorPicker.css': ['less/jquery-asColorPicker.less']
                }
            }
        },

        autoprefixer: {
            options: {
                browsers: ['last 2 versions', 'ie 8', 'ie 9', 'android 2.3', 'android 4', 'opera 12']
            },
            src: {
                expand: true,
                cwd: 'css/',
                src: ['*.css', '!*.min.css'],
                dest: 'css/'
            }
        },

        replace: {
            bower: {
                src: ['bower.json'],
                overwrite: true, // overwrite matched source files
                replacements: [{
                    from: /("version": ")([0-9\.]+)(")/g,
                    to: "$1<%= pkg.version %>$3"
                }]
            },
            jquery: {
                src: ['asColorPicker.jquery.json'],
                overwrite: true, // overwrite matched source files
                replacements: [{
                    from: /("version": ")([0-9\.]+)(")/g,
                    to: "$1<%= pkg.version %>$3"
                }]
            },
        },
        copy: {
            bower: {
                files: [{
                    expand: true,
                    flatten: true,
                    cwd: 'bower_components/',
                    src: [
                        'jquery-asColor/dist/jquery-asColor.js',
                        'jquery-asGradient/dist/jquery-asGradient.js'
                    ],
                    dest: 'libs/'
                }]
            }
        }
    });

    // These plugins provide necessary tasks.
    // Load npm plugins to provide necessary tasks.
    require('load-grunt-tasks')(grunt, {
        pattern: ['grunt-*']
    });

    // Default task.
    grunt.registerTask('default', ['js', 'dist']);

    grunt.registerTask('css', ['less', 'autoprefixer']);

    grunt.registerTask('js', ['jsbeautifier', 'jshint']);
    grunt.registerTask('dist', ['clean', 'concat', 'uglify']);

    grunt.registerTask('version', [
        'replace:bower',
        'replace:jquery'
    ]);
};
