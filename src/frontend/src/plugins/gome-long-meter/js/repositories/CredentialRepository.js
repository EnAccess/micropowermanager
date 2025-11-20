import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/gome-long-meters/gome-long-credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
