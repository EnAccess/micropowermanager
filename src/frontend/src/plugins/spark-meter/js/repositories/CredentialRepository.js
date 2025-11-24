import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/spark-meters/sm-credential`

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
