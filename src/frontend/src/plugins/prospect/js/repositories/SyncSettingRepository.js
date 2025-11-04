import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

// Align endpoint with backend Prospect plugin once available
const resource = `${baseUrl}/api/prospect/prospect-setting/sync-setting`

export default {
  getSyncSettings() {
    return Client.get(`${resource}`)
  },
  updateSyncSettings(syncListPayload) {
    // Send raw array payload like other meter plugins
    return Client.put(`${resource}`, syncListPayload)
  },
}
