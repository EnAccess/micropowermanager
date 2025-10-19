import Client, { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/api-keys`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(payload) {
    return Client.post(`${resource}`, payload)
  },
  remove(id) {
    return Client.delete(`${resource}/${id}`)
  },
}
