import Vue from "vue"
import VChart from "vue-echarts"

import App from "@/App.vue"
import "@/assets/sass/app.scss"
import "@/charts/echarts.js"
import i18n from "@/i18n.js"
import router from "@/router.js"
import store from "@/store/store.js"

Vue.component("v-chart", VChart)

new Vue({
  el: "#app",
  router,
  store,
  i18n,
  render: (h) => h(App),
})
