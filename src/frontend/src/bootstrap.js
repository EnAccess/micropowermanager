window._ = require("lodash")
window.Popper = require("popper.js").default
import "babel-polyfill"

window.axios = require("axios")
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest"
// Add a request interceptor
window.axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token")
    if (token) {
      config.headers["Authorization"] = "Bearer " + token
    }
    // config.headers['Content-Type'] = 'application/json';
    return config
  },
  (error) => {
    Promise.reject(error)
  },
)

import { config } from "./config"

Vue.prototype.appConfig = config

import Vue from "vue"

/**
 * Vue Router
 */
import VueRouter from "vue-router"

Vue.use(VueRouter)

/**
 * Vuex
 */
import Vuex from "vuex"

Vue.use(Vuex)
window.Vue = Vue
window.Vuex = Vuex

/**
 * VeeValidate
 */
import i18n from "./i18n"
import VeeValidate from "vee-validate"
import enMessages from "vee-validate/dist/locale/en"
import frMessages from "vee-validate/dist/locale/fr"

Vue.use(VeeValidate, {
  i18n,
  dictionary: {
    en: enMessages,
    fr: frMessages,
    bu: enMessages, // No burmese error messages available
  },
})

/**
 * VueGoogleCharts
 */
import VueGoogleCharts from "vue-google-charts"

Vue.use(VueGoogleCharts)

/**
 * moment
 */
import moment from "moment"

window.moment = moment

/**
 * Vue Notification
 */
// import Notifications from "vue-notification"

// Vue.use(Notifications)

/**
 * Reources
 */
import { resources } from "./resources"

window.resources = resources

/**
 * Pusher
 */
window.Pusher = require("pusher-js")

/**
 * Sweet Alert
 */
import VueSweetalert2 from "vue-sweetalert2"

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
import VueMaterial from "vue-material"
import "vue-material/dist/vue-material.min.css"
import "vue-material/dist/theme/default.css" // This line here
Vue.use(VueMaterial)

/**
 * SidebarComponent
 */
import SidebarComponent from "@/modules/Sidebar"

Vue.use(SidebarComponent)

/**
 * Some SCSS
 */
import "../src/assets/sass/mpm.scss"

/**
 * Default Layout
 */
import Default from "./layouts/Default.vue"

Vue.component("default-layout", Default)

/**
 * VueTelInput
 */
// FIXME: It's only used once. Should this really be a global import?
import VueTelInput from "vue-tel-input"
import "vue-tel-input/dist/vue-tel-input.css"

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
