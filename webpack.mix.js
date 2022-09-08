const mix = require('laravel-mix');
const path = require("path");
const scanForWidgets = require('./resources/js/widgetScanner.js');
const config = require('./webpack.config');

mix.webpackConfig(config);
mix.babelConfig({
    plugins: ['@babel/plugin-syntax-dynamic-import'],
});

const widgetsPaths = scanForWidgets(path.join(__dirname, "resources", "js", "widgets"));
widgetsPaths.forEach(widgetPath => {
    mix.js(widgetPath[0], widgetPath[1]).vue();
});

mix.sass('resources/sass/main.scss', 'public/css/main.css')
    .sass('resources/sass/SuperAdmin/admin.scss', 'public/css/SuperAdmin/admin.css')
    .sass('resources/sass/auth.scss', 'public/css/auth.css').version();

mix.js('resources/js/components/Common/DashboardsSwitchWidget/main.js',
    'public/js/components/DashboardsSwitchWidget.js').version();

mix.js('resources/js/app.js', 'public/js/').extract(['bootstrap', 'jquery', 'vue', 'bootstrap-vue', 'select2']).version();
