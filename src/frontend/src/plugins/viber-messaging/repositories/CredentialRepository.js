import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/viber-messaging/viber-credential`

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
