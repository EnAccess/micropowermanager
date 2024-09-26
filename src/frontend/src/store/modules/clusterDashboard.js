import { ClustersDashboardCacheDataService } from "@/services/ClustersDashboardCacheDataService"

export const namespaced = true

export const state = {
  clustersDashboardCacheDataService: new ClustersDashboardCacheDataService(),
  clustersCacheData: [],
  clusterCacheData: {
    id: null,
    citiesRevenue: [],
    revenueAnalysis: {},
    clusterData: {},
  },
}
export const mutations = {
  SET_CLUSTERS_DATA(state, clustersCacheData) {
    state.clustersCacheData = clustersCacheData
  },
  SET_CLUSTER_DATA(state, id) {
    state.clusterCacheData = state.clustersCacheData.reduce((acc, curr) => {
      if (curr.id === parseInt(id)) {
        acc = { ...curr }
      }
      return acc
    }, {})
  },
}
export const actions = {
  update({ commit, state }) {
    return state.clustersDashboardCacheDataService
      .update()
      .then((response) => {
        commit("SET_CLUSTERS_DATA", response)
      })
      .catch((error) => {
        throw error
      })
  },
  list({ commit, state }) {
    return state.clustersDashboardCacheDataService
      .list()
      .then((response) => {
        commit("SET_CLUSTERS_DATA", response)
      })
      .catch((error) => {
        throw error
      })
  },
  get({ commit }, id) {
    commit("SET_CLUSTER_DATA", id)
  },
}
export const getters = {
  getClustersData: (state) => state.clustersCacheData,
  getClustersRevenue: (state) => state.clustersCacheData.clustersRevenue,
  getClusterData: (state) => state.clusterCacheData,
}
