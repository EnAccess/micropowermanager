import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/spark-meters/sm-transaction`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  sync() {
    return Client.get(`${resource}/sync`)
  },
}
