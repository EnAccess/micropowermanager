import { CompanyService } from "@/services/CompanyService"
import { ProtectedPageService } from "@/services/ProtectedPageService"
import store from "@/store/store"

export const namespaced = true

export const state = {
  companyService: new CompanyService(),
  protectedPageService: new ProtectedPageService(),
  password: "",
  protectedPages: [],
}
export const mutations = {
  SET_PROTECTED_PAGES(state, protectedPages) {
    state.protectedPages = protectedPages
  },
  SET_PROTECTED_PAGE_PASSWORD(state, password) {
    state.password = password
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
  getProtectedPagePassword({ commit }) {
    return new Promise((resolve, reject) => {
      const user = store.getters["auth/getAuthenticateUser"]
      state.companyService
        .getCompanyByUser(user)
        .then((company) => {
          commit("SET_PROTECTED_PAGE_PASSWORD", company.protected_page_password)
          resolve(company.protected_page_password)
        })
        .catch((e) => {
          reject(e)
        })
    })
  },
}

export const getters = {
  getProtectedPages: (state) => state.protectedPages,
  getPassword: (state) => state.password,
}
