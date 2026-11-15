// https://vitepress.dev/guide/custom-theme
import { h } from "vue"
import type { Theme } from "vitepress"
import DefaultTheme from "vitepress/theme"
import "./style.css"
import Roadmap from "./components/Roadmap.vue"
import Cloud from "./components/Cloud.vue"
import PluginOverview from "./components/PluginOverview.vue"

export default {
  extends: DefaultTheme,
  Layout: () => {
    return h(DefaultTheme.Layout, null, {
      // https://vitepress.dev/guide/extending-default-theme#layout-slots
    })
  },
  enhanceApp({ app, router, siteData }) {
    app.component("Roadmap", Roadmap)
    app.component("Cloud", Cloud)
    app.component("PluginOverview", PluginOverview)
  },
} satisfies Theme
