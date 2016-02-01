module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		
		watch: {
			css: {
				files: ['assets/scss/*.scss'],
				tasks: ['sass'],
				options: {
					livereload: true,
					spawn: false,
				},
			},
			js: {
				files: ['assets/js/*.js'],
				tasks: ['uglify'],
				options: {
					livereload: true,
					spawn: false,
				},
			},
		},
		
		connect: {
			options: {
				port: 9000,
				livereload: 35729,
				hostname: 'localhost'
			},
			livereload: {
				options: {
					open: true,
					base: 'build'
				}
			},
		},
		
		uglify: {
			build: {
				src: 'assets/js/app.js',
				dest: '../vip-staging-script.js'
			}
		},
		
		sass: {
			dist: {
				options: {
					sourcemap: 'none',
					style: 'compressed'
				},
				files: {
					'../vip-staging-style.css' : 'assets/scss/styles.scss'
				}
			}
		},
		
	});

	// Watch
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.event.on('watch', function(action, filepath, target) {
		grunt.log.writeln(target + ': ' + filepath + ' has ' + action);
	});
	
	// Server
	grunt.loadNpmTasks('grunt-contrib-connect');
	grunt.registerTask('serv', function (target) {
	    grunt.task.run([
	        'connect:livereload',
	        'watch'
		]);
	});
	
	// Uglify/Minify
	grunt.loadNpmTasks('grunt-contrib-uglify');

	// Sass/SCSS
	grunt.loadNpmTasks('grunt-contrib-sass');

	// Default task(s).
	grunt.registerTask('default', ['uglify', 'sass']);

};