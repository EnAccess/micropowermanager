import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/steama-meters/steama-setting/sync-setting`

export default {
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
