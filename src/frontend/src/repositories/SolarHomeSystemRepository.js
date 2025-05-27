import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/solar-home-systems`

export default {
  create(shs) {
    return Client.post(`${resource}`, shs)
  },
  detail(shsId) {
    return Client.get(`${resource}/${shsId}`)
  },
  transactions(shsId) {
    return Client.get(`${resource}/${shsId}/transactions`)
  },
  update(shsId, shs) {
    return Client.put(`${resource}/${shsId}`, shs)
  },
  delete(shsId) {
    return Client.delete(`${resource}/${shsId}`)
  },
  resource,
}
