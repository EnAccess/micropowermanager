import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

export const resource = `${baseUrl}/api/plugins`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(mpmPluginId, plugin) {
    return Client.put(`${resource}/${mpmPluginId}`, plugin)
  },
}
