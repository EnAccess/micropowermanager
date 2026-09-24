const ImportMetaEnvPlugin = require("@import-meta-env/unplugin")

module.exports = {
  lintOnSave: false,
  devServer: {
    allowedHosts: "all",
  },
  css: {
    loaderOptions: {
      scss: {
        // Prepended to every SCSS block, so components must not declare their own
        // `@use`/`@forward` — those must come first in a file.
        prependData: `@use "@/assets/sass/tokens" as *;`,
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
