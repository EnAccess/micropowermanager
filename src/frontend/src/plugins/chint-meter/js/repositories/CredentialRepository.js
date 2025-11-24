import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/chint-meters/chint-credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
