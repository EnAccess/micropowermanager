import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/connection-groups`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(name) {
    return Client.post(`${resource}`, name)
  },
  update(connectionGroup) {
    return Client.put(`${resource}/${connectionGroup.id}`, connectionGroup)
  },
}
