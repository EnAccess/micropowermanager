import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/sms-body`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  update(smsBodies) {
    return Client.put(`${resource}`, smsBodies)
  },
}
