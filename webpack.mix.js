let mix = require('laravel-mix');
let path = require('path');
//mix.setPublicPath(path.normalize(`./src`));
//mix.setResourceRoot(path.normalize(`src/web/dist`));

mix.js('src/web/src/js/mux-field.js', 'src/web/dist/js/').vue();
mix.js('src/web/src/js/mux-domain-restrictions.js', 'src/web/dist/js/').vue();
mix.js('src/web/src/js/mux-signed-keys.js', 'src/web/dist/js/').vue();

mix.css('src/web/src/css/mux-element-edit.css', 'src/web/dist/css/');

// if(mix.inProduction()) {
// }