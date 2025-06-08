const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
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
                '@': path.resolve(__dirname, 'dist')
            }
        },

        entry: {
            // JavaScript principal de dev-tools (SOLO archivos existentes)
            'dev-tools': path.resolve(__dirname, 'src/js/dev-tools.js'),
            'dev-utils': path.resolve(__dirname, 'src/js/dev-utils.js'),
            
            // Dashboard Module - Arquitectura 3.0
            'dashboard': path.resolve(__dirname, 'src/js/dashboard.js'),
            
            // System Info Module - Arquitectura 3.0
            'system-info': path.resolve(__dirname, 'src/js/system-info.js'),
            
            // Cache Module - Arquitectura 3.0
            'cache': path.resolve(__dirname, 'src/js/cache.js'),
            
            // CSS principal
            'dev-tools-styles': path.resolve(__dirname, 'src/scss/dev-tools.scss'),
            
            // TODO: Agregar cuando se creen más módulos:
            // 'ajax-tester': path.resolve(__dirname, 'src/js/ajax-tester.js'),
            // 'logs': path.resolve(__dirname, 'src/js/logs.js'),
        },

        output: {
            filename: 'js/[name].min.js',
            path: path.resolve(__dirname, 'dist'),
            clean: !isDevelopment
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
                                    corejs: '3.23',
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
                }
            ]
        },

        plugins: [
            // Extraer CSS a archivos separados
            new MiniCssExtractPlugin({
                filename: 'css/[name].min.css'
            }),

            // Limpiar directorio de salida en producción
            ...(isDevelopment ? [] : [
                new CleanWebpackPlugin({
                    cleanOnceBeforeBuildPatterns: ['**/*.map']
                })
            ])
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
            ]
        },

        // Configuración del servidor de desarrollo (opcional)
        devServer: {
            static: {
                directory: path.join(__dirname, 'dist')
            },
            hot: true,
            open: false
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
