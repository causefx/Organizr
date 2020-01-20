module.exports = function(grunt) {
    "use strict"

    var banner =
        "/*\n" +
        " * jsGrid v<%= pkg.version %> (<%= pkg.homepage %>)\n" +
        " * (c) <%= grunt.template.today('yyyy') %> <%= pkg.author %>\n" +
        " * Licensed under <%= pkg.license.type %> (<%= pkg.license.url %>)\n" +
        " */\n";

    grunt.initConfig({
        pkg: grunt.file.readJSON("package.json"),

        copy: {
            imgs: {
                expand: true,
                cwd: "css/",
                src: "*.png",
                dest: "dist/"
            },
            i18n: {
                expand: true,
                cwd: "src/i18n/",
                src: "*.js",
                dest: "dist/i18n/",
                rename: function(dest, src) {
                    return dest + "jsgrid-" + src;
                }
            }
        },

        concat: {
            options: {
                banner: banner + "\n",
                separator: "\n"
            },
            js: {
                src: [
                    "src/jsgrid.core.js",
                    "src/jsgrid.load-indicator.js",
                    "src/jsgrid.load-strategies.js",
                    "src/jsgrid.sort-strategies.js",
                    "src/jsgrid.validation.js",
                    "src/jsgrid.field.js",
                    "src/fields/jsgrid.field.text.js",
                    "src/fields/jsgrid.field.number.js",
                    "src/fields/jsgrid.field.textarea.js",
                    "src/fields/jsgrid.field.select.js",
                    "src/fields/jsgrid.field.checkbox.js",
                    "src/fields/jsgrid.field.control.js"
                ],
                dest: "dist/<%= pkg.name %>.js"
            },
            css: {
                src: "css/jsgrid.css",
                dest: "dist/<%= pkg.name %>.css"
            },
            theme: {
                src: "css/theme.css",
                dest: "dist/<%= pkg.name %>-theme.css"
            }
        },

        imageEmbed: {
            options: {
                deleteAfterEncoding : true
            },
            theme: {
                src: "<%= concat.theme.dest %>",
                dest: "<%= concat.theme.dest %>"
            }
        },

        uglify: {
            options : {
                banner: banner + "\n"
            },
            js: {
                src: "<%= concat.js.dest %>",
                dest: "dist/<%= pkg.name %>.min.js"
            }
        },

        cssmin: {
            options : {
                banner: banner
            },
            css: {
                src: "<%= concat.css.dest %>",
                dest: "dist/<%= pkg.name %>.min.css"
            },
            theme: {
                src: "<%= concat.theme.dest %>",
                dest: "dist/<%= pkg.name %>-theme.min.css"
            }
        },

        qunit: {
            files: ["tests/index.html"]
        }

    });

    grunt.loadNpmTasks("grunt-contrib-copy");
    grunt.loadNpmTasks("grunt-contrib-concat");
    grunt.loadNpmTasks("grunt-contrib-uglify");
    grunt.loadNpmTasks("grunt-image-embed");
    grunt.loadNpmTasks("grunt-contrib-cssmin");
    grunt.loadNpmTasks("grunt-contrib-qunit");

    grunt.registerTask("default", ["copy", "concat", "imageEmbed", "uglify", "cssmin"]);

    grunt.registerTask("test", "qunit");
};
