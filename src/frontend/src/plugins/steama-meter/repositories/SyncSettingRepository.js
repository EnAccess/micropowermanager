import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/steama-meters/steama-setting/sync-setting`

export default {
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
