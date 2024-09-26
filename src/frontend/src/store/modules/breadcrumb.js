export const namespaced = true

export const state = {
  breadcrumb: [],
}
export const mutations = {
  UPDATE_BREADCRUMB(state, breadcrumb) {
    state.breadcrumb = breadcrumb
  },
}
export const actions = {
  setBreadcrumb({ commit }, breadcrumb) {
    return new Promise((resolve) => {
      commit("UPDATE_BREADCRUMB", breadcrumb)
      resolve(breadcrumb)
    })
  },
}

export const getters = {
  getBreadcrumb: (state) => {
    return state.breadcrumb
  },
}
