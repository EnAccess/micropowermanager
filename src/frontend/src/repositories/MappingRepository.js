import axios from "axios"

// Nominatim is a third-party service, so it is called with plain axios rather
// than the authenticated MPM client: the user's API token must not be sent there.
const NOMINATIM_SEARCH = "https://nominatim.openstreetmap.org/search"

export default {
  get(name) {
    return axios.get(NOMINATIM_SEARCH, {
      params: { q: name, polygon_geojson: 1, format: "geojson" },
    })
  },
  getCountry(country) {
    return axios.get(NOMINATIM_SEARCH, {
      params: { country, format: "json", limit: 1 },
      timeout: 3000,
    })
  },
}
