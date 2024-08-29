export const namespaced = true

export const state = {
  resolution: {
    width: 0,
    height: 0,
    isMobile: false,
  },
}
export const mutations = {
  UPDATE_RESOLUTION(state, resolution) {
    state.resolution = resolution
  },
}
export const actions = {
  setResolution({ commit }, resolution) {
    return new Promise((resolve) => {
      commit("UPDATE_RESOLUTION", resolution)
      resolve(resolution)
    })
  },
}

export const getters = {
  getDevice: (state) => {
    return state.resolution.isMobile
  },
}
