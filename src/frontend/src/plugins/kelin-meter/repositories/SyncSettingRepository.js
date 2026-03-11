import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/kelin-meters/kelin-setting/sync-setting`

export default {
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
