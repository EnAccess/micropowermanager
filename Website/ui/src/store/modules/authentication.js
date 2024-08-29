import { AuthenticationService } from "@/services/AuthenticationService"

export const namespaced = true

export const state = {
  service: new AuthenticationService(),
  authenticateUser: {},
  status: "",
}
export const mutations = {
  AUTH_REQUEST(state) {
    state.status = "loading"
  },
  AUTH_SUCCESS(state, user) {
    state.status = "success"
    state.token = user.token
    state.authenticateUser = user
  },
  AUTH_ERROR(state) {
    state.status = "error"
  },
  SET_LOGOUT(state) {
    state.service = new AuthenticationService()
    state.status = ""
    state.token = ""
    state.authenticateUser = {}
  },
}
export const actions = {
  authenticate({ dispatch, commit, state }, { email, password }) {
    commit("AUTH_REQUEST")
    return new Promise((resolve, reject) => {
      state.service
        .authenticate(email, password)
        .then((user) => {
          commit("AUTH_SUCCESS", user)
          resolve(user)
          dispatch("settings/getSettings", null, { root: true })
        })
        .catch((e) => {
          commit("AUTH_ERROR")
          reject(e)
        })
    })
  },
  refreshToken({ dispatch, commit, state }, token) {
    commit("AUTH_REQUEST")
    return new Promise((resolve, reject) => {
      state.service
        .refreshToken(token, state.authenticateUser.intervalId)
        .then((user) => {
          commit("AUTH_SUCCESS", user)
          resolve(user)
          dispatch("settings/getSettings", null, { root: true })
        })
        .catch((e) => {
          commit("AUTH_ERROR")
          reject(e)
        })
    })
  },
  logOut({ commit, state }) {
    return new Promise((resolve, reject) => {
      state.service
        .logOut(state.authenticateUser.intervalId)
        .then(() => {
          localStorage.removeItem("token")
          commit("SET_LOGOUT")
          resolve()
        })
        .catch((e) => {
          commit("AUTH_ERROR")
          reject(e)
        })
    })
  },
}
export const getters = {
  getAuthenticateUser: (state) => {
    return state.authenticateUser
  },
  getToken: (state) => {
    return state.authenticateUser.token
  },
  getIntervalId: (state) => {
    return state.authenticateUser.intervalId
  },
  authenticationService: (state) => state.service,
  getStatus: (state) => state.status,
}
