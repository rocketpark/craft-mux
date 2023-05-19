let mix = require('laravel-mix');
let path = require('path');
//mix.setPublicPath(path.normalize(`./src`));
//mix.setResourceRoot(path.normalize(`src/web/dist`));

mix.js('src/web/src/js/mux-field.js', 'src/web/dist').vue();
mix.js('src/web/src/js/mux-dashboard.js', 'src/web/dist').vue();
mix.js('src/web/src/js/mux-settings.js', 'src/web/dist').vue();

mix.css('src/web/src/css/mux-element-edit.css', 'src/web/dist');

if(mix.inProduction()) {
    mix.minify('src/web/dist/mux-field.js');
    mix.minify('src/web/dist/mux-dashboard.js');
    mix.minify('src/web/dist/mux-settings.js');
}