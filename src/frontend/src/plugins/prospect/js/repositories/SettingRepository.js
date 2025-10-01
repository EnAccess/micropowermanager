import { baseUrl } from "@/repositories/Client/AxiosClient"
import Client from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/prospect/settings`

export default {
  getSyncSettings() {
    return Client.get(`${resource}/sync`)
  },
}
