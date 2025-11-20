import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/micro-star-meters/micro-star-credential`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
}
