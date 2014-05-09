module.exports = function(config){
    config.set({
    basePath : '../../',

    files : [
      'test/artifacts/**/*.js',
      'test/lib/**/*.js',
      'test/unit/**/*.js',
      'js/sound/SoundDecoder.js',
      'js/sound/**/*.js',
      'js/util/**/*.js',
      'js/**/*.js',
      'node_modules/jasmine-jquery/lib/jasmine-jquery.js',
      'node_modules/underscore/underscore.js'
    ],

    exclude : [
    ],

    preprocessors: {
        '*.html': ['html2js'],
        'js/**/*.js': ['coverage']
    },

    autoWatch : true,

    frameworks: ['jasmine'],

    browsers : ['Chrome'],

    reporters: ['progress', 'coverage'],

    plugins : [
      'karma-jasmine',
      'jasmine-jquery',
      'karma-coverage',
      'karma-html2js-preprocessor',
      'karma-chrome-launcher',
      'karma-firefox-launcher'
   ]
})}
