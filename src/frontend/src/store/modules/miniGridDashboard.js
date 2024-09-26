import { MiniGridDashboardCacheDataService } from "@/services/MiniGridDashboardCacheDataService"

export const namespaced = true

export const state = {
  miniGridDashboardCacheDataService: new MiniGridDashboardCacheDataService(),
  miniGridsCacheData: [],
  miniGridCacheData: {
    id: null,
    period: {},
    targets: {},
    tickets: [],
    miniGridData: {},
    totalConnections: 0,
    newConnections: 0,
    revenue: 0,
  },
}
export const mutations = {
  SET_MINI_GRIDS_DATA(state, miniGridsCacheData) {
    state.miniGridsCacheData = miniGridsCacheData
  },
  SET_MINI_GRID_DATA(state, id) {
    state.miniGridCacheData = state.miniGridsCacheData.reduce((acc, curr) => {
      if (curr.id === parseInt(id)) {
        acc = { ...curr }
      }
      return acc
    }, {})
  },
}
export const actions = {
  update({ commit, state }) {
    return state.miniGridDashboardCacheDataService
      .update()
      .then((response) => {
        commit("SET_MINI_GRIDS_DATA", response)
      })
      .catch((error) => {
        throw error
      })
  },
  list({ commit, state }) {
    return state.miniGridDashboardCacheDataService
      .list()
      .then((response) => {
        commit("SET_MINI_GRIDS_DATA", response)
      })
      .catch((error) => {
        throw error
      })
  },
  get({ commit }, id) {
    commit("SET_MINI_GRID_DATA", id)
  },
  updateByPeriod({ commit, state }, { from, to }) {
    return state.miniGridDashboardCacheDataService
      .update(from, to)
      .then((response) => {
        commit("SET_MINI_GRIDS_DATA", response)
      })
      .catch((error) => {
        throw error
      })
  },
}
export const getters = {
  getMiniGridsData: (state) => state.miniGridsCacheData,
  getMiniGridData: (state) => state.miniGridCacheData,
}
