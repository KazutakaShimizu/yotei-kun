module.exports = function (grunt) {
  grunt.initConfig({
    sass: {
      dist: {
        options: {
          style: 'expanded'
        },
        files: {
          'web/css/main.css': 'web/css/sass/main.scss'
        }
      }
    },
    watch: {
      scss: {
        files: [
          'web/css/sass/parts/*.scss'
        ],
        tasks: ['sass']
      },
      html: {
        files: '*.html',
        tasks: []
      },
      scripts: {
        files: 'common/js/*.js',
        tasks: []
      },
      options: {
        livereload: true
      }
    },
    connect: {
      server: {
        options: {
          open: true,
          port: 1234,
          hostname: 'localhost',
          livereload: 35729
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-contrib-sass');

  grunt.registerTask('default', ['connect', 'watch']);
};
