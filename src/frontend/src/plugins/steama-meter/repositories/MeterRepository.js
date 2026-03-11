import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/steama-meters/steama-meter`

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
  count() {
    return Client.get(`${resource}/count`)
  },
}
