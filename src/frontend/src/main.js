/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { EventBus } from "@/shared/eventbus"

require("./bootstrap")
import router from "./routes"
import App from "./App"
import "leaflet/dist/leaflet.css"
import store from "./store/store"
import UserData from "./shared/UserData"
import Default from "./layouts/Default"
import i18n from "./i18n"
import { MapSettingsService } from "./services/MapSettingsService"
import { MainSettingsService } from "./services/MainSettingsService"
import Steamaco from "@/plugins/steama-meter/js/modules/Overview/Credential"
import Spark from "@/plugins/spark-meter/js/modules/Overview/Credential"
import Calin from "@/plugins/calin-meter/js/modules/Overview/Credential"
import CalinSmart from "@/plugins/calin-smart-meter/js/modules/Overview/Credential"
import Kelin from "@/plugins/kelin-meter/js/modules/Overview/Credential"
import Stron from "@/plugins/stron-meter/js/modules/Overview/Credential"
import Settings from "@/modules/Settings/Configuration/MainSettings"
import Viber from "@/plugins/viber-messaging/js/modules/Overview/Credential"
import MicroStar from "@/plugins/micro-star-meter/js/modules/Overview/Credential"
import SunKing from "@/plugins/sun-king-shs/js/modules/Overview/Credential"
import WaveMoney from "@/plugins/wave-money-payment-provider/js/modules/Overview/Credential"
import GomeLong from "@/plugins/gome-long-meter/js/modules/Overview/Credential"
import WaveComTransaction from "@/plugins/wavecom-payment-provider/js/modules/Component"
import WaveComTransactionDetail from "@/modules/Transactions/WaveComTransactionDetail"
import AirtelTransactionDetail from "@/modules/Transactions/AirtelTransactionDetail"
import SwiftaTransactionDetail from "@/modules/Transactions/SwiftaTransactionDetail"
import ThirdPartyTransactionDetail from "@/modules/Transactions/ThirdPartyTransactionDetail"
import VodacomTransactionDetail from "@/modules/Transactions/VodacomTransactionDetail"
import WaveMoneyTransactionDetail from "@/modules/Transactions/WaveMoneyTransactionDetail"
import AgentTransactionDetail from "@/modules/Agent/AgentTransactionDetail"
import Angaza from "@/plugins/angaza-shs/js/modules/Overview/Credential"
import DalyBms from "@/plugins/daly-bms/js/modules/Overview/Credential"
import AfricasTalking from "@/plugins/africas-talking/js/modules/Overview/Credential"
import Snackbar from "@/shared/Snackbar.vue"
import ChintMeter from "@/plugins/chint-meter/js/modules/Overview/Credential"

Vue.component("default", Default)
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
Vue.component("AirtelTransactionDetail", AirtelTransactionDetail)
Vue.component("SwiftaTransactionDetail", SwiftaTransactionDetail)
Vue.component("ThirdPartyTransactionDetail", ThirdPartyTransactionDetail)
Vue.component("VodacomTransactionDetail", VodacomTransactionDetail)
Vue.component("WaveMoneyTransactionDetail", WaveMoneyTransactionDetail)
Vue.component("AgentTransactionDetail", AgentTransactionDetail)
Vue.component("Angaza-SHS", Angaza)
Vue.component("Daly-Bms", DalyBms)
Vue.component("Africas-Talking", AfricasTalking)
Vue.component("Snackbar", Snackbar)
Vue.component("Chint-Meter", ChintMeter)

const unauthorizedPaths = [
  "login",
  "forgot-password",
  "welcome",
  "register",
  "/wave-money/payment",
  "/wave-money/result",
]

router.beforeEach((to, from, next) => {
  const authToken = store.getters["auth/getToken"]
  const intervalId = store.getters["auth/getIntervalId"]
  if (unauthorizedPaths.includes(to.name)) {
    EventBus.$emit("checkPageProtection", to)
    return next()
  }
  if (authToken === undefined || authToken === "") {
    return next({ name: "welcome" })
  }

  store
    .dispatch("auth/refreshToken", authToken, intervalId)
    .then((result) => {
      if (result) {
        EventBus.$emit("checkPageProtection", to)
        return next()
      }
      next({ name: "login" })
    })
    .catch(() => {
      return next({ name: "welcome" })
    })
})

/*eslint-disable */
const app = new Vue({
  el: "#app",
  components: {
    UserData,
  },
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
