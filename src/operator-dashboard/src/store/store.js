import Vue from "vue"
import Vuex from "vuex"

import * as operatorDashboard from "@/store/modules/operatorDashboard.js"
import * as session from "@/store/modules/session.js"

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    operatorDashboard,
    session,
  },
})
