import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/spark-meters/sm-setting/sync-setting`

export default {
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
