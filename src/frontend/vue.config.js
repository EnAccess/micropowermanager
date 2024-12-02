const ImportMetaEnvPlugin = require("@import-meta-env/unplugin")

module.exports = {
  lintOnSave: false,
  devServer: {
    allowedHosts: "all",
  },
  configureWebpack: {
    performance: {
      hints: false,
    },
    plugins: [
      ImportMetaEnvPlugin.webpack({
        example: ".env.example",
        env: ".env",
      }),
    ],
  },
}
