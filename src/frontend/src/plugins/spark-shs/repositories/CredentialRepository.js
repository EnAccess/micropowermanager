import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/spark-shs/credentials`

export default {
  get() {
    return Client.get(`${resource}`)
  },
  put(credentials) {
    return Client.put(`${resource}`, credentials)
  },
  check(credentials) {
    return Client.post(`${resource}/check`, credentials)
  },
}
