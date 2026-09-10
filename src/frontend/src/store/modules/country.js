import CountryService from "@/services/CountryService.js"

const countryService = new CountryService()

export const namespaced = true

export const state = {
  countries: [],
}
export const mutations = {
  FETCH_COUNTRIES(state, payload) {
    state.countries = payload
  },
}
export const actions = {
  // The country list is the same for every tenant and never changes while the
  // app is open, so it is fetched once and shared by every country field.
  async setCountries({ commit, state }) {
    if (state.countries.length) return state.countries

    const countries = await countryService.getCountries()
    commit("FETCH_COUNTRIES", countries)

    return countries
  },
}
export const getters = {
  getCountries: (state) => state.countries,
}
