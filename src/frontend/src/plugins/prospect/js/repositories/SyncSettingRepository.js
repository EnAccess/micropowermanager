import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/prospect/sync-settings`

export default {
  updateSyncSettings(syncList) {
    return Client.put(`${resource}`, { sync_settings: syncList })
  },
}
