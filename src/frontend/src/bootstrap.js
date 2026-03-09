import { use } from "echarts"
import { BarChart, LineChart, PieChart } from "echarts/charts.js"
import {
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent,
  ToolboxComponent,
} from "echarts/components.js"
import "vue-material/dist/vue-material.min.css"
import "vue-material/dist/theme/default.css"
import { CanvasRenderer } from "echarts/renderers.js"
import moment from "moment"
import VeeValidate from "vee-validate"
import "vue-tel-input/dist/vue-tel-input.css"
import "babel-polyfill"
import enMessages from "vee-validate/dist/locale/en.js"
import frMessages from "vee-validate/dist/locale/fr.js"
import Vue from "vue"
import VChart from "vue-echarts"
import VueMaterial from "vue-material"
import VueRouter from "vue-router"
import VueSweetalert2 from "vue-sweetalert2"
import VueTelInput from "vue-tel-input"
import Vuex from "vuex"

import { config } from "./config.js"
import i18n from "./i18n.js"
import Default from "./layouts/Default.vue"
import { resources } from "./resources.js"

import SidebarComponent from "@/modules/Sidebar/index.js"

import "../src/assets/sass/mpm.scss"

window._ = require("lodash")
window.Popper = require("popper.js").default

window.axios = require("axios")
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"

Vue.prototype.appConfig = config

/**
 * Vue Router
 */
Vue.use(VueRouter)

/**
 * Vuex
 */
Vue.use(Vuex)
window.Vue = Vue
window.Vuex = Vuex

/**
 * VeeValidate
 */
Vue.use(VeeValidate, {
  i18n,
  dictionary: {
    en: enMessages,
    fr: frMessages,
    bu: enMessages, // No burmese error messages available
  },
})

/**
 * ECharts (vue-echarts 7.x with ECharts 5.x)
 */
use([
  CanvasRenderer,
  BarChart,
  LineChart,
  PieChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent,
  ToolboxComponent,
])

Vue.component("v-chart", VChart)

/**
 * moment
 */
window.moment = moment

/**
 * Vue Notification
 */
// import Notifications from "vue-notification"

// Vue.use(Notifications)

/**
 * Reources
 */
window.resources = resources

/**
 * Pusher
 */
window.Pusher = require("pusher-js")

/**
 * Sweet Alert
 */
Vue.use(VueSweetalert2)

window.onclick = function (e) {
  let target = e.target
  if (target.localName === "a" || target.localName === "i") {
    let className = target.getAttribute("class")
    let validClassNames = [
      "fa fa-compress",
      "fa fa-expand",
      "button-icon jarviswidget-fullscreen-btn",
    ]
    if (validClassNames.indexOf(className) > -1) {
      window.dispatchEvent(new Event("resize"))
    }
  }
}

/**
 * VueMaterial
 */
Vue.use(VueMaterial)

/**
 * SidebarComponent
 */
Vue.use(SidebarComponent)

/**
 * Some SCSS
 */

/**
 * Default Layout
 */
Vue.component("default-layout", Default)

/**
 * VueTelInput
 */
// FIXME: It's only used once. Should this really be global?
const opt = {
  dropdownOptions: {
    disabledDialCode: false,
    showSearchBox: true,
  },
  inputOptions: {
    showDialCode: true,
  },
}
Vue.use(VueTelInput, opt)
