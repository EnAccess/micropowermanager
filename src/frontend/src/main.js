/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require("./bootstrap.js")
import "leaflet/dist/leaflet.css"

import App from "./App.vue"
import i18n from "./i18n.js"
import Default from "./layouts/Default.vue"
import router from "./routes.js"
import { MainSettingsService } from "./services/MainSettingsService.js"
import { MapSettingsService } from "./services/MapSettingsService.js"
import store from "./store/store.js"

import {
  getPermissionsForRoute,
  userHasPermissions,
} from "@/Helpers/PermissionGuard.js"
import AgentTransactionDetail from "@/modules/Agent/AgentTransactionDetail.vue"
import Settings from "@/modules/Settings/Configuration/MainSettings.vue"
import PaystackTransactionDetail from "@/modules/Transactions/PaystackTransactionDetail.vue"
import SmsTransactionDetail from "@/modules/Transactions/SmsTransactionDetail.vue"
import SwiftaTransactionDetail from "@/modules/Transactions/SwiftaTransactionDetail.vue"
import ThirdPartyTransactionDetail from "@/modules/Transactions/ThirdPartyTransactionDetail.vue"
import VodacomMzTransactionDetail from "@/modules/Transactions/VodacomMzTransactionDetail.vue"
import WaveComTransactionDetail from "@/modules/Transactions/WaveComTransactionDetail.vue"
import WaveMoneyTransactionDetail from "@/modules/Transactions/WaveMoneyTransactionDetail.vue"
import AfricasTalking from "@/plugins/africas-talking/modules/Overview/Credential.vue"
import Angaza from "@/plugins/angaza-shs/modules/Overview/Credential.vue"
import Calin from "@/plugins/calin-meter/modules/Overview/Credential.vue"
import CalinSmart from "@/plugins/calin-smart-meter/modules/Overview/Credential.vue"
import ChintMeter from "@/plugins/chint-meter/modules/Overview/Credential.vue"
import DalyBms from "@/plugins/daly-bms/modules/Overview/Credential.vue"
import GomeLong from "@/plugins/gome-long-meter/modules/Overview/Credential.vue"
import Kelin from "@/plugins/kelin-meter/modules/Overview/Credential.vue"
import MicroStar from "@/plugins/micro-star-meter/modules/Overview/Credential.vue"
import PaystackPaymentProvider from "@/plugins/paystack-payment-provider/Component.vue"
import PesapalPaymentProvider from "@/plugins/pesapal-payment-provider/Component.vue"
import Prospect from "@/plugins/prospect/modules/Overview/Credential.vue"
import SmsTransactionParserSetup from "@/plugins/sms-transaction-parser/modules/Overview/Setup.vue"
import Spark from "@/plugins/spark-meter/modules/Overview/Credential.vue"
import SparkShs from "@/plugins/spark-shs/modules/Overview/Credential.vue"
import Steamaco from "@/plugins/steama-meter/modules/Overview/Credential.vue"
import Stron from "@/plugins/stron-meter/modules/Overview/Credential.vue"
import SunKing from "@/plugins/sun-king-shs/modules/Overview/Credential.vue"
import TextbeeSmsGateway from "@/plugins/textbee-sms-gateway/modules/Overview/Credential.vue"
import Viber from "@/plugins/viber-messaging/modules/Overview/Credential.vue"
import VodacomMzPaymentProvider from "@/plugins/vodacom-mz-payment-provider/modules/Overview/Credential.vue"
import WaveMoney from "@/plugins/wave-money-payment-provider/modules/Overview/Credential.vue"
import WaveComTransaction from "@/plugins/wavecom-payment-provider/modules/Component.vue"
import { attachAuthStore } from "@/repositories/Client/AxiosClient.js"
import Snackbar from "@/shared/Snackbar.vue"

Vue.component("default", Default)
Vue.component("Snackbar", Snackbar)

Vue.component("WaveComTransaction", WaveComTransaction)
Vue.component("WaveComTransactionDetail", WaveComTransactionDetail)
Vue.component("SwiftaTransactionDetail", SwiftaTransactionDetail)
Vue.component("ThirdPartyTransactionDetail", ThirdPartyTransactionDetail)
Vue.component("VodacomMzTransactionDetail", VodacomMzTransactionDetail)
Vue.component("WaveMoneyTransactionDetail", WaveMoneyTransactionDetail)
Vue.component("PaystackTransactionDetail", PaystackTransactionDetail)
Vue.component("AgentTransactionDetail", AgentTransactionDetail)
Vue.component("SmsTransactionDetail", SmsTransactionDetail)

// Registration tail components. The registration tail keys each step on the
// plugin's name (MpmPlugin.name), so these must be registered under that exact
// name. A plugin without a registration step simply has no entry here.
Vue.component("Settings", Settings)
Vue.component("SparkMeter", Spark)
Vue.component("SteamaMeter", Steamaco)
Vue.component("CalinMeter", Calin)
Vue.component("CalinSmartMeter", CalinSmart)
Vue.component("KelinMeter", Kelin)
Vue.component("StronMeter", Stron)
Vue.component("ViberMessaging", Viber)
Vue.component("WaveMoneyPayment", WaveMoney)
Vue.component("MicroStarMeter", MicroStar)
Vue.component("SunKingSHS", SunKing)
Vue.component("GomeLongMeter", GomeLong)
Vue.component("AngazaSHS", Angaza)
Vue.component("DalyBms", DalyBms)
Vue.component("PaystackPaymentProvider", PaystackPaymentProvider)
Vue.component("PesapalPaymentProvider", PesapalPaymentProvider)
Vue.component("AfricasTalking", AfricasTalking)
Vue.component("ChintMeter", ChintMeter)
Vue.component("Prospect", Prospect)
Vue.component("TextbeeSmsGateway", TextbeeSmsGateway)
Vue.component("SparkShs", SparkShs)
Vue.component("SmsTransactionParser", SmsTransactionParserSetup)
Vue.component("VodacomMzPaymentProvider", VodacomMzPaymentProvider)
// NEW PLUGIN PLACEHOLDER (DO NOT REMOVE THIS LINE)

const toArray = (value) => {
  if (!value) {
    return []
  }
  return Array.isArray(value) ? value : [value]
}

Vue.mixin({
  computed: {
    $permissions() {
      return store.getters["auth/getPermissions"] || []
    },
  },
  methods: {
    $can(required) {
      return userHasPermissions(this.$permissions, toArray(required))
    },
    $canAny(required) {
      const permissions = toArray(required)
      if (!permissions.length) {
        return true
      }
      return permissions.some((permission) =>
        this.$permissions.includes(permission),
      )
    },
  },
})

const publicRouteNames = new Set([
  "login",
  "forgot-password",
  "reset-password",
  "welcome",
  "register",
  "/wave-money/payment",
  "/wave-money/result",
  "/paystack/public/payment",
  "/paystack/public/result",
  "/pesapal/public/payment",
  "/pesapal/public/result",
])

attachAuthStore(store)

router.beforeEach((to, from, next) => {
  if (publicRouteNames.has(to.name) || publicRouteNames.has(to.path)) {
    return next()
  }
  if (!store.getters["auth/getToken"]) {
    return next({ name: "welcome" })
  }
  const userPermissions = store.getters["auth/getPermissions"] || []
  const requiredPermissions = getPermissionsForRoute(to)
  if (!userHasPermissions(userPermissions, requiredPermissions)) {
    return next({ path: "/unauthorized" })
  }
  return next()
})

/*eslint-disable */
const app = new Vue({
  el: "#app",
  data() {
    return {
      mainSettingsService: new MainSettingsService(),
      mapSettingService: new MapSettingsService(),
      resolution: {
        width: window.innerWidth,
        height: window.innerHeight,
        isMobile: false,
      },
    }
  },
  mounted() {
    this.handleResize()
    window.addEventListener("resize", this.handleResize)
    this.$el.addEventListener("click", this.onHtmlClick)
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize)
  },
  methods: {
    handleResize() {
      this.resolution.width = window.innerWidth
      this.resolution.height = window.innerHeight
      if (this.resolution.width <= 960) {
        this.resolution.isMobile = true
      } else {
        this.resolution.isMobile = false
      }
      this.$store
        .dispatch("resolution/setResolution", this.resolution)
        .then(() => {})
        .catch((err) => {
          console.log(err)
        })
    },
  },
  router: router,
  store: store,
  i18n,
  render: (h) => h(App),
})
