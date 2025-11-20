import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/kelin-meters/kelin-setting/sync-setting`

export default {
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
