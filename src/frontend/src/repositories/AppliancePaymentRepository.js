import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/assets/payment`

export default {
  update(id, data) {
    return Client.post(`${resource}/${id}`, data)
  },
}
