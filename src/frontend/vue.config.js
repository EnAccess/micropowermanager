module.exports = {
  lintOnSave: false,
  devServer: {
    allowedHosts: "all",
  },
  configureWebpack: {
    performance: {
      hints: false,
    },
  },
}
