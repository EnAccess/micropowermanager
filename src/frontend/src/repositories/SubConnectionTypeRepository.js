import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/sub-connection-types`

export default {
  index(connectionTypeId) {
    return Client.get(`${resource}/${connectionTypeId}`)
  },
  store(subConnectionType) {
    return Client.post(`${resource}`, subConnectionType)
  },
  show() {
    return Client.get(`${resource}`)
  },
  update(subConnectionType) {
    return Client.put(`${resource}/${subConnectionType.id}`, subConnectionType)
  },
}
