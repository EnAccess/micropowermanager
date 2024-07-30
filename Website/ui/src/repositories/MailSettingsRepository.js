import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/settings`

export default {
  list() {
    return Client.get(`${resource}/mail`)
  },
  update(id, mailSettings) {
    return Client.put(`${resource}/mail/${id}`, mailSettings)
  },
  create(mailSettings) {
    return Client.post(`${resource}/mail`, mailSettings)
  },
}
