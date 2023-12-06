const config = require('cmf-webpack-config');
const { merge } = require('webpack-merge');

module.exports = merge(config(), {
  output: {
    library: 'cmf.core',
  },
});
