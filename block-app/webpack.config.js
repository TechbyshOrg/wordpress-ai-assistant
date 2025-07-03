const path = require('path');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';

    return {
        entry: './src/index.jsx',
        output: {
            path: path.resolve(__dirname, '../assets/js'),
            filename: 'block-enhancer.js',
            // clean: true, // Removed to prevent deleting other files
        },
        resolve: {
            extensions: ['.js', '.jsx'],
        },
        module: {
            rules: [
                {
                    test: /\.jsx?$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: ['@babel/preset-react'],
                        },
                    },
                },
                {
                    test: /\.css$/i,
                    use: ['style-loader', 'css-loader'],
                },
            ],
        },
        externals: {
            '@wordpress/blocks': ['wp', 'blocks'],
            '@wordpress/block-editor': ['wp', 'blockEditor'],
            '@wordpress/components': ['wp', 'components'],
            '@wordpress/compose': ['wp', 'compose'],
            '@wordpress/data': ['wp', 'data'],
            '@wordpress/element': ['wp', 'element'],
            '@wordpress/hooks': ['wp', 'hooks'],
            '@wordpress/i18n': ['wp', 'i18n'],
            '@wordpress/plugins': ['wp', 'plugins'],
            '@wordpress/editor': ['wp', 'editor']
        },
        devtool: isProduction ? false : 'source-map',
        watch: !isProduction,
    };
};



// const path = require('path');

// module.exports = (env, argv) => {
//   const isProduction = argv.mode === 'production';

//   return {
//     entry: './src/index.jsx',
//     output: {
//       path: path.resolve(__dirname, '../assets/js'), // ✅ custom output
//       filename: 'bundle.js',
//       clean: true, // removes old files
//     },
//     resolve: {
//       extensions: ['.js', '.jsx'],
//     },
//     module: {
//       rules: [
//         {
//           test: /\.jsx?$/,
//           exclude: /node_modules/,
//           use: 'babel-loader',
//         },
//         {
//           test: /\.css$/,
//           use: ['style-loader', 'css-loader'],
//         },
//       ],
//     },
//     devtool: isProduction ? false : 'source-map',
//     watch: !isProduction, // only watch in dev mode
//   };
// };
