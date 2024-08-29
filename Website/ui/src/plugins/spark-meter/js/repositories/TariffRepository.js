import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/spark-meters/sm-tariff`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  sync() {
    return Client.get(`${resource}/sync`)
  },
  syncCheck() {
    return Client.get(`${resource}/sync-check`)
  },
  get(id) {
    return Client.get(`${resource}/information/${id}`)
  },
  put(tariff) {
    return Client.put(`${resource}`, tariff)
  },
  count() {
    return Client.get(`${resource}/count`)
  },
}
