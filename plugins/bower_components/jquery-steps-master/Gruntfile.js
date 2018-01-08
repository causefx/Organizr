/*jshint node:true*/
module.exports = function (grunt)
{
    "use strict";

    /* Hint: Using grunt-strip-code to remove comments from the release file */

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
                separator: '\r\n\r\n',
                banner: '/*! <%= "\\r\\n * " + pkg.title %> v<%= pkg.version %> - <%= grunt.template.today("mm/dd/yyyy") + "\\r\\n" %>' +
                    ' * Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %> <%= (pkg.homepage ? "(" + pkg.homepage + ")" : "") + "\\r\\n" %>' +
                    ' * Licensed under <%= pkg.licenses[0].type + " " + pkg.licenses[0].url + "\\r\\n */\\r\\n" %>' + 
                    ';(function ($, undefined)\r\n{\r\n',
                footer: '\r\n})(jQuery);'
            },
            dist: {
                files: {
                    '<%= pkg.folders.dist %>/jquery.steps.js': [
                        '<%= pkg.folders.src %>/helper.js',
                        '<%= pkg.folders.src %>/privates.js',
                        '<%= pkg.folders.src %>/publics.js',
                        '<%= pkg.folders.src %>/enums.js',
                        '<%= pkg.folders.src %>/model.js',
                        '<%= pkg.folders.src %>/defaults.js'
                    ]
                }
            }
        },
        //"regex-replace": {
        //    all: {
        //        src: ['<%= pkg.folders.nuget %>/jQuery.Steps.nuspec'],
        //        actions: [
        //            {
        //                name: 'versionNumber',
        //                search: /<version>.*?<\/version>/gi,
        //                replace: '<version><%= pkg.version %></version>'
        //            }
        //        ]
        //    }
        //},
        exec: {
            createPkg: {
                cmd: "<%= pkg.folders.nuget %>\\Nuget pack <%= pkg.folders.nuget %>\\jQuery.Steps.nuspec -OutputDirectory <%= pkg.folders.dist %> -Version <%= pkg.version %>"
            }
        },
        compress: {
            main: {
                options: {
                    archive: '<%= pkg.folders.dist %>/jquery.steps-<%= pkg.version %>.zip'
                },
                files: [
                  { flatten: true, expand: true, src: ['<%= pkg.folders.dist %>/*.js'], dest: '/' }
                ]
            }
        },
        uglify: {
            options: {
                preserveComments: 'some',
                report: 'gzip'
            },
            all: {
                files: {
                    '<%= pkg.folders.dist %>/jquery.steps.min.js': ['<%= pkg.folders.dist %>/jquery.steps.js']
                }
            }
        },
        qunit: {
            files: ['test/index.html']
        },
        jshint: {
            options: {
                curly: true,
                eqeqeq: true,
                immed: true,
                latedef: true,
                newcap: true,
                noarg: true,
                sub: true,
                undef: true,
                eqnull: true,
                browser: true,
                globals: {
                    jQuery: true,
                    $: true,
                    console: true
                }
            },
            files: ['<%= pkg.folders.dist %>/jquery.steps.js'],
            test: {
                options: {
                    globals: {
                        jQuery: true,
                        $: true,
                        QUnit: true,
                        module: true,
                        test: true,
                        start: true,
                        stop: true,
                        expect: true,
                        ok: true,
                        equal: true,
                        deepEqual: true,
                        strictEqual: true
                    }
                },
                files: {
                    src: [
                        'test/tests.js'
                    ]
                }
            },
            grunt: {
                files: {
                    src: [
                        'Gruntfile.js'
                    ]
                }
            }
        },
        yuidoc: {
            compile: {
                name: '<%= pkg.name %>',
                description: '<%= pkg.description %>',
                version: '<%= pkg.version %>',
                url: '<%= pkg.homepage %>',
                options: {
                    exclude: 'qunit-1.11.0.js',
                    paths: '.',
                    outdir: '<%= pkg.folders.docs %>/'
                }
            }
        },
        clean: {
            api: ["<%= pkg.folders.docs %>"],
            build: ["<%= pkg.folders.dist %>"]
        }
    });

    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-qunit');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-yuidoc');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-regex-replace');
    grunt.loadNpmTasks('grunt-exec');

    grunt.registerTask('default', ['build']);
    grunt.registerTask('api', ['clean:api', 'yuidoc']);
    grunt.registerTask('build', ['clean:build', 'concat', 'jshint', 'qunit']);
    grunt.registerTask('release', ['build', 'api', 'uglify', 'compress', 'exec:createPkg']);
};