import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/map-settings`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(id, mapSettings) {
    return Client.put(`${resource}/${id}`, mapSettings)
  },
}
