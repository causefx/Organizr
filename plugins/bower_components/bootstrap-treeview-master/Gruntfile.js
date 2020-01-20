module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'), // the package file to use
 
    uglify: {
      files: {
        expand: true, 
        flatten: true, 
        src: 'src/js/*.js',
        dest: 'dist',
        ext: '.min.js'
      }
    },

    cssmin: {
      minify: {
        expand: true,
        cwd: 'src/css',
        src: ['*.css', '!*.min.css'],
        dest: 'dist',
        ext: '.min.css'
      }
    },

    qunit: {
        all: ['tests/*.html']
    },

    watch: {
      files: ['tests/*.js', 'tests/*.html', 'src/**'],
      tasks: ['default']
    },

    copy: {
      main: { 
        files: [
          // copy dist to tests
          // { expand: true, cwd: 'dist', src: '*', dest: 'tests/lib/' },
          { expand: true, cwd: 'src/css', src: '*', dest: 'tests/lib/' },
          { expand: true, cwd: 'src/js', src: '*', dest: 'tests/lib/' },
          // copy latest libs to tests
          { expand: true, cwd: 'public/bower_components/jquery', src: 'jquery.js', dest: 'tests/lib/' },
          { expand: true, cwd: 'public/bower_components/bootstrap-datepicker/js', src: 'bootstrap-datepicker.js', dest: 'tests/lib/' },
          // copy src to example
          { expand: true, cwd: 'src/css', src: '*', dest: 'public/css/' },
          { expand: true, cwd: 'src/js', src: '*', dest: 'public/js/' }
        ]
      }
    }
  });

  // load up your plugins
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-qunit');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-copy');

  // register one or more task lists (you should ALWAYS have a "default" task list)
  grunt.registerTask('default', ['uglify','cssmin', 'copy', 'qunit', 'watch']);
  grunt.registerTask('test', 'qunit');
};
