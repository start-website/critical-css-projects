"use strict";

const path = require('path'),
    gulp = require('gulp'),
    webpack = require('webpack-stream'),
    clean = require('gulp-clean'),
    cache = require('gulp-cache');

const babel = require("gulp-babel");

const isDev = process.env.NODE_ENV === 'development';

// Настройка Webpack
const webpackConfig = {
    mode: isDev ? 'development' : 'production',
    entry: {
        scripts: './src/js/_scripts.js',
        'backend-webasyst': './src/js/_backend-webasyst.js',
    },
    output: {
        filename: '[name].js',
    },
    optimization: {
        // We no not want to minimize our code.
        minimize: false
    },
    module: {
        rules: [
            {
                test: /\.m?js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    // options: {
                    //   presets: [['@babel/preset-env', {
                    //     corejs: 3,
                    //     useBuiltIns: 'usage'
                    //   }]]
                    // }
                }
            }

        ]
    }
};

// Webpack - сборщик js модулей
gulp.task('webpack', function () {
    return gulp.src([
        './src/js/_scripts.js',
        './src/js/_backend-webasyst.js'
    ])
        .pipe(webpack(webpackConfig))
        .pipe(gulp.dest(isDev ? './src/js' : './dist/js'));
});

gulp.task('default', gulp.parallel('webpack'));

gulp.task('build', gulp.series('webpack'));