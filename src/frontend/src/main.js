/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require("./bootstrap")
import router from "./routes"
import App from "./App"
import "leaflet/dist/leaflet.css"
import store from "./store/store"
import Default from "./layouts/Default"
import i18n from "./i18n"
import { MapSettingsService } from "./services/MapSettingsService"
import { MainSettingsService } from "./services/MainSettingsService"
import Snackbar from "@/shared/Snackbar.vue"
import {
  getPermissionsForRoute,
  userHasPermissions,
} from "@/Helpers/PermissionGuard"

Vue.component("default", Default)
Vue.component("Snackbar", Snackbar)

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
