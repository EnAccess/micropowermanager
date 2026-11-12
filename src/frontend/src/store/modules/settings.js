import i18n from "@/i18n.js"
import { MainSettingsService } from "@/services/MainSettingsService.js"
import { PluginService } from "@/services/PluginService.js"

const serviceMain = new MainSettingsService()

const servicePlugin = new PluginService()

export const namespaced = true

export const state = {
  mainSettings: {},
  plugins: [],
}
export const mutations = {
  FETCH_MAIN_SETTINGS(state, payload) {
    state.mainSettings = payload
    i18n.locale = payload.language
  },
  FETCH_PLUGINS(state, payload) {
    state.plugins = payload
  },
}
export const actions = {
  getSettings({ dispatch }) {
    dispatch("setMainSettings")
  },
  setMainSettings({ commit }) {
    return new Promise((resolve, reject) => {
      serviceMain
        .list()
        .then((res) => {
          commit("FETCH_MAIN_SETTINGS", res)
          resolve(res)
        })
        .catch((e) => {
          reject(e)
        })
    })
  },
  fetchPlugins({ commit, rootGetters }) {
    const permissions = rootGetters["auth/getPermissions"] || []
    if (!permissions.includes("plugins")) {
      return Promise.resolve([])
    }
    return new Promise((resolve, reject) => {
      servicePlugin
        .getPlugins()
        .then((res) => {
          commit("FETCH_PLUGINS", res)
          resolve(res)
        })
        .catch((e) => {
          reject(e)
        })
    })
  },
}

export const getters = {
  getMainSettings: (state) => {
    return state.mainSettings
  },
  getEnabledPlugins: (state) =>
    state.plugins
      .filter((item) => item.status === 1)
      .map((item) => item.mpm_plugin_id),
}
