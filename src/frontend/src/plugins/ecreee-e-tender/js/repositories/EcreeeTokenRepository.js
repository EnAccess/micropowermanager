import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/ecreee-e-tender/ecreee-token`

export default {
  create() {
    return Client.post(`${resource}`)
  },
  update(ecreeeTokenId) {
    return Client.put(`${resource}/${ecreeeTokenId}`)
  },
  get() {
    return Client.get(`${resource}`)
  },
  resource,
}
