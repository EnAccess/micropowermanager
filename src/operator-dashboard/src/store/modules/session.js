import { OperatorCredentials } from "@/services/OperatorCredentials.js"

export const namespaced = true

export const state = {
  authenticated: OperatorCredentials.read() !== null,
  error: null,
}

export const mutations = {
  SET_AUTHENTICATED(state, authenticated) {
    state.authenticated = authenticated
    if (authenticated) {
      state.error = null
    }
  },
  SET_ERROR(state, error) {
    state.error = error
  },
}

export const actions = {
  async signIn({ commit, dispatch }, { username, password }) {
    OperatorCredentials.save(username, password)

    try {
      await dispatch("operatorDashboard/fetchPlatform", null, { root: true })
      commit("SET_AUTHENTICATED", true)
    } catch (e) {
      OperatorCredentials.clear()
      commit("SET_AUTHENTICATED", false)
      commit(
        "SET_ERROR",
        e.status_code === 403
          ? "phrases.operatorNotConfigured"
          : "phrases.signInFailed",
      )
      throw e
    }
  },

  signOut({ commit }) {
    OperatorCredentials.clear()
    commit("SET_AUTHENTICATED", false)
  },
}

export const getters = {
  isAuthenticated: (state) => state.authenticated,
  error: (state) => state.error,
}
