import { RegistrationTailService } from "@/services/RegistrationTailService"

export const namespaced = true

export const state = {
  service: new RegistrationTailService(),
  registrationTail: {},
  isWizardShown: false,
}
export const mutations = {
  SET_REGISTRATION_TAIL(state, registrationTail) {
    state.registrationTail = registrationTail
  },
  SET_IS_WIZARD_SHOWN(state, param) {
    state.isWizardShown = param
  },
}
export const actions = {
  getRegistrationTail({ commit }) {
    return new Promise((resolve, reject) => {
      state.service
        .getRegistrationTail()
        .then((registrationTail) => {
          commit("SET_REGISTRATION_TAIL", registrationTail)
          resolve(registrationTail)
        })
        .catch((e) => {
          reject(e)
        })
    })
  },
}

export const getters = {
  getTail: (state) => state.registrationTail,
  getIsWizardShown: (state) => state.isWizardShown,
}
