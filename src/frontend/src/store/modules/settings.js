import { MapSettingsService } from "@/services/MapSettingsService"
import { MainSettingsService } from "@/services/MainSettingsService"
import i18n from "../../i18n"
import { PluginService } from "@/services/PluginService"

const serviceMap = new MapSettingsService()
const serviceMain = new MainSettingsService()

const servicePlugin = new PluginService()

export const namespaced = true

export const state = {
  mainSettings: {},
  mapSettings: {},
  plugins: [],
}
export const mutations = {
  FETCH_MAIN_SETTINGS(state, payload) {
    state.mainSettings = payload
    i18n.locale = payload.language
  },
  FETCH_MAP_SETTINGS(state, payload) {
    state.mapSettings = payload
  },
  FETCH_PLUGINS(state, payload) {
    state.plugins = payload
  },
}
export const actions = {
  getSettings({ dispatch }) {
    dispatch("setMainSettings")
    dispatch("setMapSettings")
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
  setMapSettings({ commit }) {
    return new Promise((resolve, reject) => {
      serviceMap
        .list()
        .then((res) => {
          commit("FETCH_MAP_SETTINGS", res)
          resolve(res)
        })
        .catch((e) => {
          reject(e)
        })
    })
  },
  fetchPlugins({ commit }) {
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
  getMapSettings: (state) => {
    return state.mapSettings
  },
  getEnabledPlugins: (state) =>
    state.plugins
      .filter((item) => item.status === 1)
      .map((item) => item.mpm_plugin_id),
}
