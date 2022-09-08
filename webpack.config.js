const path = require('path');

module.exports = {
    output: {
        chunkFilename: 'js/chunks/[name].[chunkhash].js',
        publicPath: '/'
    },
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js',
            'res': path.resolve(__dirname, 'resources'),
            '@comp': path.resolve(__dirname, 'resources/js/components/'),
            '@helpers': path.resolve(__dirname, 'resources/js/utils/Helpers/'),
            '@mixins': path.resolve(__dirname, 'resources/js/utils/Mixins/'),
        }
    }
}
