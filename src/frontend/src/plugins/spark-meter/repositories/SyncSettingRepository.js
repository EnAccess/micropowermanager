import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/spark-meters/sm-setting/sync-setting`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
