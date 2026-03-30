import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/spark-meters/sm-setting`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
