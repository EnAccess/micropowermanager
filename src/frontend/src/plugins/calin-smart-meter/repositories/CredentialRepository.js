import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/calin-smart-meters/calin-smart-credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
  check() {
    return Client.get(`${resource}/check`)
  },
}
