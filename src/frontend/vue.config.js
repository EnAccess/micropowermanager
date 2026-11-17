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
    module: {
      rules: [
        {
          // maplibre-gl's tile worker (see MapEngine.js). Emit it as a plain .js
          // file: module workers need a JavaScript MIME type, and not every web
          // server maps the .mjs extension to one.
          test: /maplibre-gl-worker\.mjs$/,
          type: "asset/resource",
          generator: { filename: "js/[name].[contenthash:8].js" },
        },
      ],
    },
    plugins: [
      ImportMetaEnvPlugin.webpack({
        example: ".env.example",
        env: ".env",
      }),
    ],
  },
}
