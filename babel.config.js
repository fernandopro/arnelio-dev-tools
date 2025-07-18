module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        useBuiltIns: 'usage',
        corejs: '3.33',
        targets: {
          browsers: ['> 1%', 'last 2 versions', 'IE 11']
        }
      }
    ]
  ],
  plugins: []
};
