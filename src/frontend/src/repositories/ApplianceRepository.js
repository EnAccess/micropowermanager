import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/assets`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(appliance) {
    return Client.post(`${resource}`, appliance)
  },

  update(appliance) {
    return Client.put(`${resource}/${appliance.id}`, appliance)
  },

  delete(id) {
    return Client.delete(`${resource}/${id}`)
  },
}
