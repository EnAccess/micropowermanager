import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/time-of-usages`

export default {
  delete(id) {
    return Client.delete(`${resource}/${id}`)
  },
}
