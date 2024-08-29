import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/dashboard/clusters`

export default {
  list() {
    return Client.get(`${resource}`)
  },

  update() {
    return Client.put(`${resource}`)
  },

  detail(id) {
    return Client.get(`${resource}/${id}`)
  },
}
