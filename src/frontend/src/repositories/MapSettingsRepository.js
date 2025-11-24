import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/map-settings`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(id, mapSettings) {
    return Client.put(`${resource}/${id}`, mapSettings)
  },
}
