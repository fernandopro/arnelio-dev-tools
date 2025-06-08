module.exports = {
  plugins: [
    require('autoprefixer')({
      overrideBrowserslist: [
        '> 1%', 
        'last 2 versions', 
        'iOS >= 8', 
        'IE 11'
      ],
      cascade: false,
      grid: 'autoplace'
    })
  ]
};
