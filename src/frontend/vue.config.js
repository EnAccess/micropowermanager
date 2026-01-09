const ImportMetaEnvPlugin = require("@import-meta-env/unplugin")

module.exports = {
  lintOnSave: false,
  devServer: {
    allowedHosts: "all",
  },
  css: {
    loaderOptions: {
      scss: {
        prependData: `@use "@/assets/sass/brand" as *;`,
      },
    },
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
