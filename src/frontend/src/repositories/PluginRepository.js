import Client from "@/repositories/Client/AxiosClient"

export const resource = `/api/plugins`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(mpmPluginId, plugin) {
    return Client.put(`${resource}/${mpmPluginId}`, plugin)
  },
}
