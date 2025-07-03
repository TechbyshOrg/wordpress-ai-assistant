// const path = require('path');

// module.exports = {
//   entry: './src/index.jsx',
//   output: {
//     path: path.resolve(__dirname, '../assets/js'),
//     filename: 'app.js',
//   },
//   module: {
//     rules: [
//       {
//         test: /\.(js|jsx)$/,
//         exclude: /node_modules/,
//         use: {
//           loader: 'babel-loader',
//           options: {
//             presets: ['@babel/preset-react']
//           }
//         }
//       },
//       {
//         test: /\.css$/i,
//         use: ['style-loader', 'css-loader'],
//       }
//     ]
//   },
//   resolve: {
//     extensions: ['.js', '.jsx']
//   },
//   externals: {
//     '@wordpress/blocks': 'wp.blocks',
//     '@wordpress/element': 'wp.element',
//     '@wordpress/block-editor': 'wp.blockEditor',
//     '@wordpress/components': 'wp.components',
//     '@wordpress/hooks': 'wp.hooks',
//     '@wordpress/i18n': 'wp.i18n'
//   },
//   mode: 'development',
//   watch: true
// };



const path = require('path');

module.exports = {
  entry: './src/index.jsx',
  output: {
    path: path.resolve(__dirname, '../assets/js'),
    filename: 'app.js',
  },
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: 'babel-loader'
      },
      {
        test: /\.css$/i,
        use: ['style-loader', 'css-loader'],
      }
    ]
  },
  resolve: {
    extensions: ['.js', '.jsx']
  },
  mode: 'development',
  watch: true
};
