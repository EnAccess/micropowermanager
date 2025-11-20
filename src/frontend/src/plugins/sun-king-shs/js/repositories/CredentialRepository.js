import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/sun-king-shs/sun-king-credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
