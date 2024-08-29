import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/kelin-meters/kelin-setting/sync-setting`

export default {
  update(syncListPM) {
    return Client.put(`${resource}`, syncListPM)
  },
}
