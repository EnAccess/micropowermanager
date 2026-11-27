import Client from "@/repositories/Client/AxiosClient.js"

export const resource = `/api/plugins`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(mpmPluginSlug, plugin) {
    return Client.put(`${resource}/${mpmPluginSlug}`, plugin)
  },
}
