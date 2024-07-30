import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/tickets`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  detail(id) {
    return Client.get(`${resource}/${id}`)
  },
}
