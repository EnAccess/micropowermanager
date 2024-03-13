import { TicketSettingsService } from '@/services/TicketSettingsService'
import { MapSettingsService } from '@/services/MapSettingsService'
import { MainSettingsService } from '@/services/MainSettingsService'
import i18n from '../../i18n'
import { SidebarService } from '@/services/SidebarService'

export const namespaced = true

export const state = {
    serviceMap: new MapSettingsService(),
    serviceMain: new MainSettingsService(),
    serviceTicket: new TicketSettingsService(),
    serviceSidebar: new SidebarService(),
    mainSettings: {},
    ticketSettings: {},
    mapSettings: {},
    sidebar: [],
}
export const mutations = {
    FETCH_MAIN_SETTINGS(state, payload) {
        state.mainSettings = payload
        i18n.locale = payload.language
    },
    FETCH_MAP_SETTINGS(state, payload) {
        state.mapSettings = payload
    },
    FETCH_TICKET_SETTINGS(state, payload) {
        state.ticketSettings = payload
    },
    SET_SIDEBAR(state, payload) {
        state.sidebar = payload
    },
}
export const actions = {
    getSettings({ dispatch }) {
        dispatch('setMainSettings')
        dispatch('setMapSettings')
        dispatch('setTicketSettings')
    },
    setMainSettings({ commit }) {
        return new Promise((resolve, reject) => {
            state.serviceMain
                .list()
                .then((res) => {
                    commit('FETCH_MAIN_SETTINGS', res)
                    resolve(res)
                })
                .catch((e) => {
                    reject(e)
                })
        })
    },
    setMapSettings({ commit }) {
        return new Promise((resolve, reject) => {
            state.serviceMap
                .list()
                .then((res) => {
                    commit('FETCH_MAP_SETTINGS', res)
                    resolve(res)
                })
                .catch((e) => {
                    reject(e)
                })
        })
    },
    setTicketSettings({ commit }) {
        return new Promise((resolve, reject) => {
            state.serviceTicket
                .list()
                .then((res) => {
                    commit('FETCH_TICKET_SETTINGS', res)
                    resolve(res)
                })
                .catch((e) => {
                    reject(e)
                })
        })
    },
    setSidebar({ commit }, sidebar = null) {
        if (sidebar) {
            commit('SET_SIDEBAR', sidebar)
        } else {
            return new Promise((resolve, reject) => {
                state.serviceSidebar
                    .list()
                    .then((res) => {
                        commit('SET_SIDEBAR', res)
                        resolve(res)
                    })
                    .catch((e) => {
                        reject(e)
                    })
            })
        }
    },
}

export const getters = {
    getMainSettings: (state) => {
        return state.mainSettings
    },
    getMapSettings: (state) => {
        return state.mapSettings
    },
    getTicketSettings: (state) => {
        return state.ticketSettings
    },
    mainSettingsService: (state) => state.serviceMain,
    mapSettingsService: (state) => state.serviceMap,
    ticketSettingsService: (state) => state.serviceTicket,
    getSidebar: (state) => state.sidebar,
}
