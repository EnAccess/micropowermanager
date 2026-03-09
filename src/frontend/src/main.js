/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require("./bootstrap.js")
import App from "./App.vue"
import i18n from "./i18n.js"
import Default from "./layouts/Default.vue"
import router from "./routes.js"
import "leaflet/dist/leaflet.css"
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
import SwiftaTransactionDetail from "@/modules/Transactions/SwiftaTransactionDetail.vue"
import ThirdPartyTransactionDetail from "@/modules/Transactions/ThirdPartyTransactionDetail.vue"
import VodacomTransactionDetail from "@/modules/Transactions/VodacomTransactionDetail.vue"
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
import Paystack from "@/plugins/paystack-payment-provider/modules/Overview/Credential.vue"
import Prospect from "@/plugins/prospect/modules/Overview/Credential.vue"
import Spark from "@/plugins/spark-meter/modules/Overview/Credential.vue"
import SparkShs from "@/plugins/spark-shs/modules/Overview/Credential.vue"
import Steamaco from "@/plugins/steama-meter/modules/Overview/Credential.vue"
import Stron from "@/plugins/stron-meter/modules/Overview/Credential.vue"
import SunKing from "@/plugins/sun-king-shs/modules/Overview/Credential.vue"
import TextbeeSmsGateway from "@/plugins/textbee-sms-gateway/modules/Overview/Credential.vue"
import Viber from "@/plugins/viber-messaging/modules/Overview/Credential.vue"
import WaveMoney from "@/plugins/wave-money-payment-provider/modules/Overview/Credential.vue"
import WaveComTransaction from "@/plugins/wavecom-payment-provider/modules/Component.vue"
import Snackbar from "@/shared/Snackbar.vue"

Vue.component("default", Default)
Vue.component("Snackbar", Snackbar)

// global component to be displayed in RegistrationTail
Vue.component("Spark-Meter", Spark)
Vue.component("Steamaco-Meter", Steamaco)
Vue.component("Calin-Meter", Calin)
Vue.component("CalinSmart-Meter", CalinSmart)
Vue.component("Kelin-Meter", Kelin)
Vue.component("Stron-Meter", Stron)
Vue.component("Settings", Settings)
Vue.component("Viber-Messaging", Viber)
Vue.component("WaveMoney", WaveMoney)
Vue.component("MicroStar-Meter", MicroStar)
Vue.component("SunKing-SHS", SunKing)
Vue.component("GomeLong-Meter", GomeLong)
Vue.component("WaveComTransaction", WaveComTransaction)
Vue.component("WaveComTransactionDetail", WaveComTransactionDetail)
Vue.component("SwiftaTransactionDetail", SwiftaTransactionDetail)
Vue.component("ThirdPartyTransactionDetail", ThirdPartyTransactionDetail)
Vue.component("VodacomTransactionDetail", VodacomTransactionDetail)
Vue.component("WaveMoneyTransactionDetail", WaveMoneyTransactionDetail)
Vue.component("PaystackTransactionDetail", PaystackTransactionDetail)
Vue.component("AgentTransactionDetail", AgentTransactionDetail)
Vue.component("Angaza-SHS", Angaza)
Vue.component("Daly-Bms", DalyBms)
Vue.component("Paystack-Payment-Provider", PaystackPaymentProvider)
Vue.component("Africas-Talking", AfricasTalking)
Vue.component("Chint-Meter", ChintMeter)
Vue.component("Prospect", Prospect)
Vue.component("Paystack", Paystack)
Vue.component("TextbeeSmsGateway", TextbeeSmsGateway)
Vue.component("SparkShs", SparkShs)
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
])

router.beforeEach(async (to, from, next) => {
  const authToken = store.getters["auth/getToken"]
  const intervalId = store.getters["auth/getIntervalId"]
  if (publicRouteNames.has(to.name) || publicRouteNames.has(to.path)) {
    return next()
  }
  if (!authToken) {
    return next({ name: "welcome" })
  }

  try {
    const result = await store.dispatch(
      "auth/refreshToken",
      authToken,
      intervalId,
    )
    if (!result) {
      return next({ name: "login" })
    }
    const userPermissions = store.getters["auth/getPermissions"] || []
    const requiredPermissions = getPermissionsForRoute(to)
    if (!userHasPermissions(userPermissions, requiredPermissions)) {
      return next({ path: "/unauthorized" })
    }
    return next()
  } catch (error) {
    return next({ name: "welcome" })
  }
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
