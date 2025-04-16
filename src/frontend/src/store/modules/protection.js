import { ProtectedPageService } from "@/services/ProtectedPageService"

export const namespaced = true

export const state = {
  protectedPageService: new ProtectedPageService(),
  protectedPages: [],
}
export const mutations = {
  SET_PROTECTED_PAGES(state, protectedPages) {
    state.protectedPages = protectedPages
  },
}
export const actions = {
  getProtectedPages({ commit }) {
    return new Promise((resolve, reject) => {
      state.protectedPageService
        .getProtectedPages()
        .then((protectedPages) => {
          commit(
            "SET_PROTECTED_PAGES",
            protectedPages.map((protectedPage) => protectedPage.name),
          )
          resolve(protectedPages)
        })
        .catch((e) => {
          reject(e)
        })
    })
  },
}

export const getters = {
  getProtectedPages: (state) => state.protectedPages,
}
