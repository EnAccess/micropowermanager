import Vue from "vue"
import Vuex from "vuex"
import { Person } from "@/services/PersonService"
import * as auth from "@/store/modules/authentication"
import * as settings from "@/store/modules/settings"
import * as resolution from "@/store/modules/resolution"
import * as breadcrumb from "@/store/modules/breadcrumb"
import * as registrationTail from "@/store/modules/registrationTail"
import * as clusterDashboard from "@/store/modules/clusterDashboard"
import * as miniGridDashboard from "@/store/modules/miniGridDashboard"
import * as protection from "@/store/modules/protection"
import * as device from "@/store/modules/device"
import VuexPersist from "vuex-persist"

Vue.use(Vuex)
const vuexLocalStorage = new VuexPersist({
  reducer: (state) => ({
    auth: {
      authenticateUser: state.auth.authenticateUser,
    },
    settings: {
      mainSettings: state.settings.mainSettings,

      mapSettings: state.settings.mapSettings,
    },
    resolution: {
      width: state.resolution.width,
      height: state.resolution.height,
      isMobile: state.resolution.isMobile,
    },
    breadcrumb: {
      breadcrumb: state.breadcrumb,
    },
    clusterDashboard: {
      clustersCacheData: state.clusterDashboard.clustersCacheData,
    },
    registrationTail: {
      registrationTail: state.registrationTail.registrationTail,
      isWizardShown: state.registrationTail.isWizardShown,
    },
    protection: {
      protectedPages: state.protection.protectedPages,
      password: state.protection.password,
    },
    miniGridsDashboard: {
      miniGridsCacheData: state.miniGridsCacheData,
    },
  }),
  key: "vuex",
  storage: window.localStorage,
})
export default new Vuex.Store({
  modules: {
    auth,
    settings,
    resolution,
    breadcrumb,
    clusterDashboard,
    registrationTail,
    protection,
    miniGridDashboard,
    device,
  },
  plugins: [vuexLocalStorage.plugin],
  state: {
    person: new Person(),
    devices: [],
    search: {},
  },
  getters: {
    person: (state) => state.person,
    devices: (state) => state.devices,
    search: (state) => state.search,
    resolution: (state) => state.resolution,
    breadcrumb: (state) => state.breadcrumb,
  },
})
