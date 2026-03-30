import Vue from "vue"
import Vuex from "vuex"
import VuexPersist from "vuex-persist"

import { Person } from "@/services/PersonService.js"
import * as auth from "@/store/modules/authentication.js"
import * as breadcrumb from "@/store/modules/breadcrumb.js"
import * as clusterDashboard from "@/store/modules/clusterDashboard.js"
import * as device from "@/store/modules/device.js"
import * as miniGridDashboard from "@/store/modules/miniGridDashboard.js"
import * as registrationTail from "@/store/modules/registrationTail.js"
import * as resolution from "@/store/modules/resolution.js"
import * as settings from "@/store/modules/settings.js"

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
