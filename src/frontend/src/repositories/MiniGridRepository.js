import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/mini-grids`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(miniGridPM) {
    return Client.post(`${resource}`, miniGridPM)
  },
  get(miniGridId) {
    return Client.get(`${resource}/${miniGridId}`)
  },
  geoData(miniGridId) {
    return Client.get(`${resource}/${miniGridId}?relation=1`)
  },
  transactions(miniGridId, period) {
    return Client.post(`${resource}/${miniGridId}/transactions`, period)
  },
  soldEnergy(miniGridId, period) {
    return Client.post(`${resource}/${miniGridId}/energy`, period)
  },
}
