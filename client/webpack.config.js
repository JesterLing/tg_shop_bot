const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const webpack = require('webpack');
const dotenv = require('dotenv').config({ path: '../.env' }); 

module.exports = (env, argv) => {
  const devMode = argv.mode === 'development';
  let config = {
    entry: './react-app/App.jsx',
    output: {
      path: path.resolve(__dirname, '../public', 'dist'),
      clean: devMode ? false : true
    },
    resolve: {
      extensions: ['.jsx', '.js', '.tsx', '.ts', '.json']
    },
    cache: devMode ? true : false,
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react'],
              plugins: ['@babel/plugin-transform-runtime'],
              cacheDirectory: devMode ? true : false
            }
          }
        },
        {
          test: /\.css$/i,
          use: ['style-loader', 'css-loader']
        },
        {
          test: /\.less$/i,
          use: [
            devMode ? 'style-loader' : MiniCssExtractPlugin.loader,
            'css-loader',
            'less-loader'
          ],
          exclude: /\.module\.less$/
        },
        {
          test: /\.less$/i,
          use: [
            devMode ? 'style-loader' : MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                modules: {
                  localIdentName: '[local]-[hash:base64:5]'
                }
              }
            },
            'less-loader'
          ],
          include: /\.module\.less$/
        },
        {
          test: /\.(png|jpe?g|gif|svg|webp|ico)$/i,
          type: devMode ? 'asset/resource' : 'asset'
        },
        {
          test: /\.(woff2?|eot|ttf|otf)$/i,
          type: 'asset/resource'
        }
      ]
    },
    optimization: {
      minimize: devMode ? false : true,
      splitChunks: {
        cacheGroups: {
          styles: {
            name: 'styles',
            type: 'css/mini-extract',
            chunks: 'all',
            enforce: true
          }
        }
      }
    },
    plugins: [
      new HtmlWebpackPlugin({
        template: './react-app/index.html',
        filename: 'template.html',
        publicPath: '/dist',
        hash: true
      }),
      new webpack.DefinePlugin({
        'process.env.PUBLIC_URL': process.env.PUBLIC_URL,
        'process.env.VERSION': 0.8
      })
    ].concat(
      devMode ? [] : [new MiniCssExtractPlugin({ filename: '[name].css', ignoreOrder: true })]
    )
  };

  if (devMode) {
    config.devtool = 'source-map';
  }

  return config;
};
