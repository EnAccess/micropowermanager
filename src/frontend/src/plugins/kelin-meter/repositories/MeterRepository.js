import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/kelin-meters/kelin-meter`

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
}
