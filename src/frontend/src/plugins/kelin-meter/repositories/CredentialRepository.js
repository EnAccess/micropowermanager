import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/kelin-meters/kelin-credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
