const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = (env, argv) => {
    const mode = argv.mode || 'development';
    const isDevelopment = mode === 'development';

    return {
        mode: mode,
        devtool: isDevelopment ? 'source-map' : false,
        
        resolve: {
            modules: [path.resolve(__dirname, 'src'), 'node_modules'],
            alias: {
                '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
                '@': path.resolve(__dirname, 'dist'),
                '@assets': path.resolve(__dirname, 'assets'),
                '@modules': path.resolve(__dirname, 'modules'),
                '@includes': path.resolve(__dirname, 'includes')
            }
        },

        entry: {
            // JavaScript principal de dev-tools
            'dev-tools': path.resolve(__dirname, 'src/js/dev-tools.js'),
            
            // CSS principal - Bootstrap 5 + Custom
            'dev-tools-styles': path.resolve(__dirname, 'src/scss/dev-tools.scss')
        },

        output: {
            filename: 'js/[name].min.js',
            path: path.resolve(__dirname, 'dist'),
            clean: !isDevelopment,
            publicPath: ''
        },

        module: {
            rules: [
                // JavaScript con Babel
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                ['@babel/preset-env', {
                                    useBuiltIns: 'usage',
                                    corejs: '3.33',
                                    targets: {
                                        browsers: ['> 1%', 'last 2 versions', 'IE 11']
                                    }
                                }]
                            ]
                        }
                    }
                },

                // SCSS/CSS
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                sourceMap: isDevelopment,
                                importLoaders: 2
                            }
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                postcssOptions: {
                                    config: path.resolve(__dirname, 'postcss.config.js')
                                },
                                sourceMap: isDevelopment
                            }
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: isDevelopment,
                                sassOptions: {
                                    includePaths: ['./node_modules'],
                                    silenceDeprecations: ['legacy-js-api', 'import', 'global-builtin', 'color-functions'],
                                    quietDeps: true
                                }
                            }
                        }
                    ]
                },

                // CSS puro
                {
                    test: /\.css$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                sourceMap: isDevelopment
                            }
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                postcssOptions: {
                                    config: path.resolve(__dirname, 'postcss.config.js')
                                },
                                sourceMap: isDevelopment
                            }
                        }
                    ]
                },

                // Fonts y recursos
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/,
                    type: 'asset/resource',
                    generator: {
                        filename: 'fonts/[name][ext]'
                    }
                },

                // Imágenes
                {
                    test: /\.(png|jpe?g|gif|svg)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'images/[name][ext]'
                    }
                }
            ]
        },

        plugins: [
            // Extraer CSS a archivos separados
            new MiniCssExtractPlugin({
                filename: 'css/[name].min.css',
                chunkFilename: 'css/[id].css'
            })
        ],

        optimization: {
            minimizer: [
                new CssMinimizerPlugin({
                    minimizerOptions: {
                        preset: [
                            'default',
                            {
                                discardComments: { removeAll: true }
                            }
                        ]
                    }
                }),
                new TerserPlugin({
                    terserOptions: {
                        compress: {
                            drop_console: !isDevelopment
                        },
                        mangle: !isDevelopment,
                        format: {
                            comments: false
                        }
                    },
                    extractComments: false
                })
            ],
            
            // Optimización de chunks para mejor caching
            splitChunks: {
                cacheGroups: {
                    vendor: {
                        test: /[\\/]node_modules[\\/]/,
                        name: 'vendors',
                        chunks: 'all',
                        filename: 'js/vendors.min.js'
                    }
                }
            }
        },

        // Configuración del servidor de desarrollo (opcional)
        devServer: {
            static: {
                directory: path.join(__dirname, 'dist')
            },
            hot: true,
            open: false,
            port: 8080
        },

        // Estadísticas de compilación
        stats: {
            colors: true,
            modules: false,
            children: false,
            chunks: false,
            chunkModules: false
        }
    };
};
