import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/assets/rates`

export default {
  update(id, terms) {
    return Client.put(`${resource}/${id}`, terms)
  },
}
